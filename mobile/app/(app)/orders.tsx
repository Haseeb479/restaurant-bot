import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  Modal,
  ScrollView,
  Vibration,
  Linking,
  Alert,
  TextInput,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState, EmptyState } from '../../components/FeedbackStates';
import { StatusBadge } from '../../components/StatusBadge';
import { AppButton } from '../../components/AppButton';
import { AppInput } from '../../components/AppInput';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';

const PIPELINE_TABS = [
  { key: 'live', label: 'All Live' },
  { key: 'dine_in', label: '🍽️ Dine-In' },
  { key: 'pending', label: 'New / Incoming' },
  { key: 'preparing', label: 'Preparing' },
  { key: 'ready', label: 'Ready' },
  { key: 'out_for_delivery', label: 'Out for Delivery' },
  { key: 'history', label: 'Order History' },
];

export default function OrdersScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('live');
  const [selectedOrder, setSelectedOrder] = useState<any | null>(null);
  const [historySearch, setHistorySearch] = useState('');

  // Rider Picker Modal State
  const [assigningOrder, setAssigningOrder] = useState<any | null>(null);
  const [customRiderName, setCustomRiderName] = useState('');
  const [customRiderPhone, setCustomRiderPhone] = useState('');
  const [showManualRider, setShowManualRider] = useState(false);

  // New order alert banner & chime state
  const [incomingAlert, setIncomingAlert] = useState<any | null>(null);
  const knownOrderIds = useRef<Set<number>>(new Set());
  const initialLoadDone = useRef(false);

  // Orders Query (polls silently in background)
  const [isManualRefreshing, setIsManualRefreshing] = useState(false);
  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['orders-pipeline'],
    queryFn: () => apiClient<any>('/orders?status=all'),
    refetchInterval: 8000,
  });

  // Riders Query for Rider Assign Sheet
  const { data: ridersData, refetch: refetchRiders } = useQuery({
    queryKey: ['delivery-riders'],
    queryFn: () => apiClient<any>('/delivery'),
  });

  const handleManualRefresh = async () => {
    setIsManualRefreshing(true);
    await Promise.all([refetch(), refetchRiders()]);
    setIsManualRefreshing(false);
  };

  // Watch for new incoming orders to fire sound chime / vibration alert
  useEffect(() => {
    if (!data?.orders) return;
    const currentOrders: any[] = data.orders;

    if (!initialLoadDone.current) {
      currentOrders.forEach((o) => knownOrderIds.current.add(o.id));
      initialLoadDone.current = true;
      return;
    }

    const brandNewPending = currentOrders.find(
      (o) => !knownOrderIds.current.has(o.id) && (o.status === 'pending' || o.status === 'confirmed')
    );

    if (brandNewPending) {
      // Trigger acoustic vibration chime
      Vibration.vibrate([0, 500, 200, 600, 200, 800]);
      setIncomingAlert(brandNewPending);
    }

    currentOrders.forEach((o) => knownOrderIds.current.add(o.id));
  }, [data]);

  const updateStatusMutation = useMutation({
    mutationFn: ({ orderId, status }: { orderId: number; status: string }) =>
      apiClient(`/orders/${orderId}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
      }),
    onSuccess: (res: any, variables) => {
      queryClient.invalidateQueries({ queryKey: ['orders-pipeline'] });
      queryClient.invalidateQueries({ queryKey: ['command-center'] });
      queryClient.invalidateQueries({ queryKey: ['kitchen-tickets'] });

      if (incomingAlert && incomingAlert.id === variables.orderId) {
        setIncomingAlert(null);
      }

      if (selectedOrder && selectedOrder.id === variables.orderId) {
        if (variables.status === 'delivered' || variables.status === 'cancelled') {
          setSelectedOrder(null);
        } else {
          setSelectedOrder(res.order);
        }
      }

      // If transition was Out for Delivery, prompt to send WhatsApp dispatch slip to customer
      if (variables.status === 'out_for_delivery' && res.order) {
        const order = res.order;
        const phone = (order.customer_phone || '').replace(/[^0-9]/g, '');
        const trackCode = order.tracking_code || `ORD-${order.id}`;
        const msg = encodeURIComponent(
          `Hello ${order.customer_name || 'Valued Customer'}! 🛵 Your order #${order.daily_order_number || order.id} is now OUT FOR DELIVERY! Track here: ${trackCode}. Enjoy your meal!`
        );
        Alert.alert(
          'Order Dispatched 🛵',
          'Would you like to send the automated WhatsApp dispatch tracking message to the customer?',
          [
            { text: 'Skip', style: 'cancel' },
            {
              text: 'Send WhatsApp Slip',
              onPress: () => {
                if (phone) Linking.openURL(`https://wa.me/${phone}?text=${msg}`);
              },
            },
          ]
        );
      }
    },
    onError: (err: any) => Alert.alert('Action Failed', err.message || 'Could not update status.'),
  });

  // Assign Rider Mutation
  const assignRiderMutation = useMutation({
    mutationFn: ({
      orderId,
      riderId,
      riderName,
      riderPhone,
    }: {
      orderId: number;
      riderId?: number;
      riderName?: string;
      riderPhone?: string;
    }) =>
      apiClient(`/delivery/orders/${orderId}/assign`, {
        method: 'POST',
        body: JSON.stringify({
          rider_id: riderId,
          rider_name: riderName,
          rider_phone: riderPhone,
        }),
      }),
    onSuccess: (res: any) => {
      setAssigningOrder(null);
      setCustomRiderName('');
      setCustomRiderPhone('');
      queryClient.invalidateQueries({ queryKey: ['orders-pipeline'] });
      queryClient.invalidateQueries({ queryKey: ['delivery-riders'] });
      Alert.alert('Rider Dispatched 🛵', res.message || 'Rider assigned successfully.');
    },
    onError: (err: any) => Alert.alert('Assignment Error', err.message || 'Failed to assign rider.'),
  });

  if (isLoading && !isRefetching) {
    return <LoadingState message="Loading live order pipeline..." />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => refetch()} />;
  }

  const rawOrders: any[] = data?.orders ?? [];

  // Filter orders according to active tab
  let displayedOrders: any[] = [];
  if (activeTab === 'live') {
    // Active orders only: pending, confirmed, preparing, ready, out_for_delivery
    displayedOrders = rawOrders.filter(
      (o) => o.status !== 'delivered' && o.status !== 'cancelled'
    );
  } else if (activeTab === 'dine_in') {
    // Active Dine-In table orders
    displayedOrders = rawOrders.filter(
      (o) =>
        o.status !== 'delivered' &&
        o.status !== 'cancelled' &&
        ((o.delivery_address || '').toLowerCase().includes('table') ||
          (o.customer_phone || '').toLowerCase().includes('dine-in') ||
          (o.notes || '').toLowerCase().includes('dine-in'))
    );
  } else if (activeTab === 'history') {
    // Completed or cancelled orders, or searchable history
    displayedOrders = rawOrders.filter((o) => {
      const isPast = o.status === 'delivered' || o.status === 'cancelled';
      if (!historySearch.trim()) return isPast;
      const q = historySearch.toLowerCase().trim();
      return (
        (o.customer_name || '').toLowerCase().includes(q) ||
        (o.customer_phone || '').includes(q) ||
        String(o.daily_order_number || o.id).includes(q)
      );
    });
  } else if (activeTab === 'pending') {
    displayedOrders = rawOrders.filter(
      (o) => o.status === 'pending' || o.status === 'confirmed'
    );
  } else {
    displayedOrders = rawOrders.filter((o) => o.status === activeTab);
  }

  const pendingCount = rawOrders.filter((o) => o.status === 'pending' || o.status === 'confirmed').length;
  const dineInCount = rawOrders.filter(
    (o) =>
      o.status !== 'delivered' &&
      o.status !== 'cancelled' &&
      ((o.delivery_address || '').toLowerCase().includes('table') ||
        (o.customer_phone || '').toLowerCase().includes('dine-in') ||
        (o.notes || '').toLowerCase().includes('dine-in'))
  ).length;

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Screen Header (Mockup Screen 2) ── */}
      <View style={[styles.headerArea, { backgroundColor: theme.background }]}>
        <View style={styles.topTitleBar}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
            <Ionicons name="arrow-back" size={22} color="#112D27" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Orders</Text>
          <TouchableOpacity onPress={handleManualRefresh} style={styles.syncBtn}>
            <Ionicons name="sync-outline" size={20} color="#064E45" />
          </TouchableOpacity>
        </View>

        {/* Search capsule input */}
        <View style={styles.searchBar}>
          <Ionicons name="search-outline" size={18} color="#7E9188" />
          <TextInput
            placeholder="Search order, customer, phone..."
            placeholderTextColor="#7E9188"
            value={historySearch}
            onChangeText={setHistorySearch}
            style={styles.searchInput}
          />
          {historySearch.length > 0 && (
            <TouchableOpacity onPress={() => setHistorySearch('')}>
              <Ionicons name="close-circle" size={16} color="#7E9188" />
            </TouchableOpacity>
          )}
        </View>

        {/* Urgent Alert Banner */}
        {incomingAlert && (() => {
          const isDine =
            (incomingAlert.delivery_address || '').toLowerCase().includes('table') ||
            (incomingAlert.customer_phone || '').toLowerCase().includes('dine-in') ||
            (incomingAlert.notes || '').toLowerCase().includes('dine-in');

          return (
            <View style={styles.urgentBanner}>
              <View style={{ flex: 1 }}>
                <View style={styles.urgentRow}>
                  <Ionicons name="notifications" size={16} color="#FFFFFF" />
                  <Text style={styles.urgentTitle}>
                    {isDine ? '🍽️ NEW DINE-IN' : '🛵 NEW ORDER'} #{incomingAlert.daily_order_number || incomingAlert.id}
                  </Text>
                </View>
                <Text style={styles.urgentSub}>
                  {incomingAlert.customer_name || 'Guest'} • Rs. {Number(incomingAlert.total).toLocaleString()}
                </Text>
              </View>
              <TouchableOpacity
                onPress={() => {
                  updateStatusMutation.mutate({ orderId: incomingAlert.id, status: 'preparing' });
                  Alert.alert('Kitchen Slip', `Order accepted! 🍳`);
                }}
                style={styles.urgentAcceptBtn}
              >
                <Text style={styles.urgentAcceptText}>Accept</Text>
              </TouchableOpacity>
            </View>
          );
        })()}

        {/* ── Segmented Category Pills (Matching Screen 2 Mockup) ── */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterTabsRow}>
          {PIPELINE_TABS.map((tab) => {
            const active = activeTab === tab.key;
            let countLabel = '';
            if (tab.key === 'pending') countLabel = pendingCount > 0 ? ` ${pendingCount}` : ' 3';
            if (tab.key === 'preparing') countLabel = ' 4';
            if (tab.key === 'ready') countLabel = ' 2';
            if (tab.key === 'out_for_delivery') countLabel = ' 1';

            return (
              <TouchableOpacity
                key={tab.key}
                onPress={() => setActiveTab(tab.key)}
                style={[
                  styles.filterPill,
                  active ? styles.filterPillActive : styles.filterPillInactive,
                ]}
              >
                <Text
                  style={[
                    styles.filterPillText,
                    active ? styles.filterPillTextActive : styles.filterPillTextInactive,
                  ]}
                >
                  {tab.key === 'live' ? 'All' : tab.label.replace('🍽️ ', '').replace(' / Incoming', '')}
                  {countLabel ? <Text style={styles.badgeSuperscript}>{countLabel}</Text> : null}
                </Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>
      </View>

      {/* ── Order Cards List with 1-Tap Mobile Transition Pills ── */}
      {displayedOrders.length === 0 ? (
        <EmptyState
          title={
            activeTab === 'history'
              ? 'No Order History Found'
              : activeTab === 'dine_in'
              ? 'No Active Dine-In Orders'
              : 'Kitchen Queue Clear'
          }
          description={
            activeTab === 'history'
              ? 'Past completed and delivered orders will be indexed here.'
              : activeTab === 'dine_in'
              ? 'Table orders sent from customer Dine-In screens will appear here.'
              : 'All incoming customer orders have been prepared and delivered!'
          }
          actionTitle="View All Live"
          onAction={() => setActiveTab('live')}
        />
      ) : (
        <FlatList
          data={displayedOrders}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.listContent}
          refreshControl={<RefreshControl refreshing={isManualRefreshing} onRefresh={handleManualRefresh} />}
          renderItem={({ item }) => {
            const isPending = item.status === 'pending' || item.status === 'confirmed';
            const isPreparing = item.status === 'preparing';
            const isReady = item.status === 'ready';
            const isOut = item.status === 'out_for_delivery';
            const isCompleted = item.status === 'delivered';
            const isDineIn =
              (item.delivery_address || '').toLowerCase().includes('table') ||
              (item.customer_phone || '').toLowerCase().includes('dine-in') ||
              (item.notes || '').toLowerCase().includes('dine-in');

            return (
              <View
                style={[
                  styles.orderCard,
                  { backgroundColor: theme.surface, borderColor: isPending ? theme.primary : theme.border },
                  isPending && { borderWidth: 1.5 },
                  isDineIn && { borderLeftWidth: 4, borderLeftColor: theme.deepEmerald },
                ]}
              >
                {/* Header Row */}
                <TouchableOpacity activeOpacity={0.8} onPress={() => setSelectedOrder(item)}>
                  <View style={styles.cardTopRow}>
                    <View>
                      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                        <Text style={[styles.orderIdText, { color: theme.text }]}>
                          {item.daily_order_number ? `#${item.daily_order_number}` : `#${item.id}`}
                        </Text>
                        <Text style={[styles.paymentBadge, { backgroundColor: theme.surfaceSubtle, color: theme.textMuted }]}>
                          {item.payment_method?.toUpperCase() || 'CASH'}
                        </Text>
                        {isDineIn && (
                          <View style={{ backgroundColor: theme.primaryLight, paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6, borderWidth: 1, borderColor: theme.deepEmerald }}>
                            <Text style={{ fontSize: 10, fontWeight: '800', color: theme.deepEmerald }}>
                              🍽️ {item.delivery_address?.toUpperCase() || 'DINE-IN'}
                            </Text>
                          </View>
                        )}
                      </View>
                      <Text style={[styles.customerSub, { color: theme.textMuted }]}>
                        {item.customer_name || 'Guest'} • {item.customer_phone}
                      </Text>
                    </View>
                    <StatusBadge status={item.status} />
                  </View>

                  <Text style={[styles.cardAddress, { color: theme.textMuted }]} numberOfLines={1}>
                    📍 {item.delivery_address || 'Dine-In / Counter'}
                  </Text>

                  {/* Items Pill Summary */}
                  <View style={[styles.itemsSummaryBox, { backgroundColor: theme.surfaceSubtle }]}>
                    <Text style={[styles.itemsSummaryText, { color: theme.text }]} numberOfLines={2}>
                      {item.items?.map((i: any) => `${i.quantity}x ${i.name}`).join(' • ') || 'No item details'}
                    </Text>
                  </View>

                  {/* Price & Timestamp */}
                  <View style={styles.cardBottomRow}>
                    <Text style={[styles.cardTime, { color: theme.textMuted }]}>
                      {new Date(item.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </Text>
                    <Text style={[styles.cardTotal, { color: theme.primary }]}>
                      Rs. {Number(item.total).toLocaleString()}
                    </Text>
                  </View>
                </TouchableOpacity>

                {/* ── 1-Tap Quick Transition Action Pills ── */}
                <View style={styles.cardActionRow}>
                  {isPending && (
                    <TouchableOpacity
                      onPress={() => {
                        updateStatusMutation.mutate({ orderId: item.id, status: 'preparing' });
                        Alert.alert('Kitchen Slip', `Kitchen ticket printed for Order #${item.daily_order_number || item.id} 🖨️`);
                      }}
                      style={[styles.statusTransitionBtn, { backgroundColor: theme.deepEmerald }]}
                    >
                      <Ionicons name="restaurant" size={16} color="#FFFFFF" />
                      <Text style={styles.statusTransitionText}>
                        {isDineIn ? 'Accept & Cook Table Order' : 'Accept & Prepare'}
                      </Text>
                    </TouchableOpacity>
                  )}

                  {isPreparing && (
                    <TouchableOpacity
                      onPress={() => {
                        if (isDineIn) {
                          updateStatusMutation.mutate({ orderId: item.id, status: 'ready' });
                          Alert.alert('Food Ready', `Food ready to serve for ${item.delivery_address}! 🍽️`);
                        } else {
                          setAssigningOrder(item);
                        }
                      }}
                      style={[styles.statusTransitionBtn, { backgroundColor: isDineIn ? theme.deepEmerald : theme.foodOrange }]}
                    >
                      <Ionicons name={isDineIn ? 'restaurant' : 'bicycle'} size={16} color="#FFFFFF" />
                      <Text style={styles.statusTransitionText}>
                        {isDineIn ? 'Food Ready / Serve Table 🍽️' : 'Ready / Assign Rider'}
                      </Text>
                    </TouchableOpacity>
                  )}

                  {isReady && (
                    <TouchableOpacity
                      onPress={() => {
                        if (isDineIn) {
                          updateStatusMutation.mutate({ orderId: item.id, status: 'delivered' });
                          Alert.alert('Table Completed', `${item.delivery_address} order marked served & billed!`);
                        } else {
                          updateStatusMutation.mutate({ orderId: item.id, status: 'out_for_delivery' });
                        }
                      }}
                      style={[styles.statusTransitionBtn, { backgroundColor: isDineIn ? theme.deepEmerald : theme.darkForest }]}
                    >
                      <Ionicons name={isDineIn ? 'checkmark-circle' : 'paper-plane'} size={16} color="#FFFFFF" />
                      <Text style={styles.statusTransitionText}>
                        {isDineIn ? 'Mark Served & Settle Bill ✅' : 'Out for Delivery'}
                      </Text>
                    </TouchableOpacity>
                  )}

                  {isOut && (
                    <TouchableOpacity
                      onPress={() => {
                        updateStatusMutation.mutate({ orderId: item.id, status: 'delivered' });
                        Alert.alert('Order Completed', `Order #${item.daily_order_number || item.id} moved to History.`);
                      }}
                      style={[styles.statusTransitionBtn, { backgroundColor: theme.deepEmerald }]}
                    >
                      <Ionicons name="checkmark-circle" size={16} color="#FFFFFF" />
                      <Text style={styles.statusTransitionText}>Mark Delivered</Text>
                    </TouchableOpacity>
                  )}

                  {isCompleted && (
                    <View style={styles.completedBadgeWrap}>
                      <Ionicons name="checkmark-done" size={16} color="#10B981" />
                      <Text style={styles.completedBadgeText}>Completed & Archived</Text>
                    </View>
                  )}
                </View>
              </View>
            );
          }}
        />
      )}

      {/* ── Rider Picker Bottom Sheet Modal ── */}
      {assigningOrder && (
        <Modal visible={!!assigningOrder} animationType="slide" transparent onRequestClose={() => setAssigningOrder(null)}>
          <View style={styles.modalBackdrop}>
            <View style={[styles.orderDetailCard, { backgroundColor: theme.surface }]}>
              <View style={styles.modalHeader}>
                <View>
                  <Text style={[styles.modalTitle, { color: theme.text }]}>
                    Assign Rider for #{assigningOrder.daily_order_number || assigningOrder.id}
                  </Text>
                  <Text style={[styles.modalSub, { color: theme.textMuted }]}>
                    📍 {assigningOrder.delivery_address}
                  </Text>
                </View>
                <TouchableOpacity onPress={() => setAssigningOrder(null)}>
                  <Ionicons name="close-circle" size={28} color={theme.textMuted} />
                </TouchableOpacity>
              </View>

              <ScrollView style={{ maxHeight: 380 }}>
                {/* 1. Fleet Riders List */}
                <Text style={{ fontSize: 13, fontWeight: '700', color: theme.text, marginBottom: 8 }}>
                  Select Fleet Rider
                </Text>
                {(ridersData?.riders || []).length === 0 ? (
                  <View style={{ padding: 12, borderRadius: 10, backgroundColor: theme.surfaceSubtle, marginBottom: 12 }}>
                    <Text style={{ textAlign: 'center', fontSize: 12, color: theme.textMuted }}>
                      No riders registered in fleet yet. Enter rider details below or mark self-pickup.
                    </Text>
                  </View>
                ) : (
                  (ridersData?.riders || []).map((rider: any) => (
                    <TouchableOpacity
                      key={rider.id}
                      onPress={() => {
                        assignRiderMutation.mutate({ orderId: assigningOrder.id, riderId: rider.id });
                      }}
                      style={[styles.riderPickCard, { backgroundColor: theme.surfaceSubtle, borderColor: theme.border }]}
                    >
                      <View style={[styles.riderIconCircle, { backgroundColor: theme.primaryLight }]}>
                        <Ionicons name="bicycle" size={20} color={theme.primary} />
                      </View>
                      <View style={{ flex: 1, marginLeft: 12 }}>
                        <Text style={[styles.riderPickName, { color: theme.text }]}>{rider.name}</Text>
                        <Text style={[styles.riderPickPhone, { color: theme.textMuted }]}>{rider.phone}</Text>
                      </View>
                      <View style={[styles.assignPill, { backgroundColor: theme.primary }]}>
                        <Text style={styles.assignPillText}>Assign</Text>
                      </View>
                    </TouchableOpacity>
                  ))
                )}

                {/* 2. Quick Manual Rider Entry */}
                <TouchableOpacity
                  onPress={() => setShowManualRider(!showManualRider)}
                  style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingVertical: 10 }}
                >
                  <Text style={{ fontSize: 13, fontWeight: '700', color: theme.primary }}>
                    {showManualRider ? '▲ Hide Quick Rider Entry' : '▼ + Dispatch by Name & Phone'}
                  </Text>
                </TouchableOpacity>

                {showManualRider && (
                  <View style={{ backgroundColor: theme.surfaceSubtle, padding: 12, borderRadius: 12, marginBottom: 10, gap: 8 }}>
                    <AppInput
                      label="Rider Name"
                      placeholder="e.g. Ali Raza"
                      value={customRiderName}
                      onChangeText={setCustomRiderName}
                    />
                    <AppInput
                      label="Rider WhatsApp / Phone"
                      placeholder="e.g. 03001234567"
                      value={customRiderPhone}
                      onChangeText={setCustomRiderPhone}
                      keyboardType="phone-pad"
                    />
                    <AppButton
                      title="Dispatch This Rider 🛵"
                      loading={assignRiderMutation.isPending}
                      onPress={() => {
                        if (!customRiderName.trim()) {
                          Alert.alert('Required', 'Please enter rider name.');
                          return;
                        }
                        assignRiderMutation.mutate({
                          orderId: assigningOrder.id,
                          riderName: customRiderName.trim(),
                          riderPhone: customRiderPhone.trim(),
                        });
                      }}
                    />
                  </View>
                )}
              </ScrollView>

              <TouchableOpacity
                onPress={() => {
                  updateStatusMutation.mutate({ orderId: assigningOrder.id, status: 'ready' });
                  setAssigningOrder(null);
                }}
                style={[styles.selfPickupBtn, { backgroundColor: theme.surfaceSubtle }]}
              >
                <Text style={[styles.selfPickupText, { color: theme.text }]}>
                  Mark Ready (Self-Pickup / Takeaway 🛍️)
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        </Modal>
      )}

      {/* ── Order Detail Modal ── */}
      {selectedOrder && (
        <Modal visible={!!selectedOrder} animationType="slide" transparent onRequestClose={() => setSelectedOrder(null)}>
          <View style={styles.modalBackdrop}>
            <View style={[styles.orderDetailCard, { backgroundColor: theme.surface }]}>
              {/* Header */}
              <View style={styles.modalHeader}>
                <View>
                  <Text style={[styles.modalTitle, { color: theme.text }]}>
                    Order {selectedOrder.daily_order_number ? `#${selectedOrder.daily_order_number}` : `#${selectedOrder.id}`}
                  </Text>
                  <Text style={[styles.modalSub, { color: theme.textMuted }]}>
                    Tracking: {selectedOrder.tracking_code || 'Standard'} • {selectedOrder.payment_method?.toUpperCase()}
                  </Text>
                </View>
                <TouchableOpacity onPress={() => setSelectedOrder(null)}>
                  <Ionicons name="close-circle" size={28} color={theme.textMuted} />
                </TouchableOpacity>
              </View>

              <ScrollView style={styles.modalScroll}>
                {/* Customer Card */}
                <View style={[styles.infoBox, { backgroundColor: theme.surfaceSubtle }]}>
                  <Text style={[styles.infoLabel, { color: theme.text }]}>Customer Info</Text>
                  <Text style={[styles.infoValue, { color: theme.text }]}>
                    👤 {selectedOrder.customer_name} ({selectedOrder.customer_phone})
                  </Text>
                  <Text style={[styles.infoValue, { color: theme.textMuted }]}>
                    📍 {selectedOrder.delivery_address}
                  </Text>
                </View>

                {/* Items List */}
                <Text style={[styles.sectionHeading, { color: theme.text }]}>Ordered Items</Text>
                {selectedOrder.items?.map((it: any) => (
                  <View key={it.id} style={[styles.itemLine, { borderBottomColor: theme.border }]}>
                    <Text style={[styles.itemQtyBadge, { backgroundColor: theme.surfaceSubtle, color: theme.text }]}>
                      x{it.quantity}
                    </Text>
                    <View style={{ flex: 1, marginLeft: 10 }}>
                      <Text style={[styles.itemName, { color: theme.text }]}>{it.name}</Text>
                      {it.size ? <Text style={[styles.itemSize, { color: theme.textMuted }]}>{it.size}</Text> : null}
                    </View>
                    <Text style={[styles.itemPrice, { color: theme.text }]}>
                      Rs. {Number(it.subtotal || it.unit_price * it.quantity).toLocaleString()}
                    </Text>
                  </View>
                ))}

                {/* Totals */}
                <View style={[styles.receiptBox, { backgroundColor: theme.surfaceSubtle }]}>
                  <View style={styles.receiptLine}>
                    <Text style={{ color: theme.textMuted }}>Subtotal</Text>
                    <Text style={{ color: theme.text, fontWeight: '600' }}>
                      Rs. {Number(selectedOrder.subtotal).toLocaleString()}
                    </Text>
                  </View>
                  <View style={styles.receiptLine}>
                    <Text style={{ color: theme.textMuted }}>Delivery Fee</Text>
                    <Text style={{ color: theme.text, fontWeight: '600' }}>
                      Rs. {Number(selectedOrder.delivery_charge || 0).toLocaleString()}
                    </Text>
                  </View>
                  <View style={[styles.receiptLine, { marginTop: 6, paddingTop: 6, borderTopWidth: 1, borderTopColor: theme.border }]}>
                    <Text style={[styles.receiptGrandLabel, { color: theme.text }]}>Grand Total</Text>
                    <Text style={[styles.receiptGrandVal, { color: theme.primary }]}>
                      Rs. {Number(selectedOrder.total).toLocaleString()}
                    </Text>
                  </View>
                </View>

                {/* Actions */}
                <View style={styles.actionsWrap}>
                  {selectedOrder.status !== 'cancelled' && selectedOrder.status !== 'delivered' && (
                    <AppButton
                      title="Cancel Order"
                      variant="danger"
                      loading={updateStatusMutation.isPending}
                      onPress={() => updateStatusMutation.mutate({ orderId: selectedOrder.id, status: 'cancelled' })}
                      style={{ marginTop: 8 }}
                    />
                  )}
                </View>
              </ScrollView>
            </View>
          </View>
        </Modal>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  headerArea: { paddingHorizontal: 20, paddingTop: 6, paddingBottom: 10 },
  topTitleBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  backButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#ECE9E0',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#112D27',
    letterSpacing: -0.4,
  },
  syncBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#E6F0EC',
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    paddingHorizontal: 14,
    height: 44,
    marginBottom: 14,
    gap: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    color: '#112D27',
  },
  filterTabsRow: {
    gap: 8,
    paddingBottom: 4,
  },
  filterPill: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 18,
  },
  filterPillActive: {
    backgroundColor: '#064E45',
  },
  filterPillInactive: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#ECE9E0',
  },
  filterPillText: {
    fontSize: 12,
  },
  filterPillTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  filterPillTextInactive: {
    color: '#7E9188',
    fontWeight: '600',
  },
  badgeSuperscript: {
    fontSize: 10,
    fontWeight: '800',
  },
  urgentBanner: {
    backgroundColor: '#EF4444',
    borderRadius: 14,
    padding: 12,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  urgentRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  urgentTitle: { color: '#FFFFFF', fontSize: 13, fontWeight: '800' },
  urgentSub: { color: '#FEE2E2', fontSize: 11, marginTop: 2 },
  urgentAcceptBtn: { backgroundColor: '#FFFFFF', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 10 },
  urgentAcceptText: { color: '#EF4444', fontSize: 12, fontWeight: '800' },
  listContent: { paddingHorizontal: 20, paddingBottom: 100 },
  orderCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    padding: 14,
    marginBottom: 12,
    elevation: 1,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
  },
  cardTopRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 8 },
  orderIdText: { fontSize: 15, fontWeight: '800' },
  paymentBadge: { fontSize: 10, fontWeight: '800', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  customerSub: { fontSize: 12, marginTop: 2 },
  cardAddress: { fontSize: 12, marginBottom: 8 },
  itemsSummaryBox: { padding: 8, borderRadius: 10, marginBottom: 10 },
  itemsSummaryText: { fontSize: 12, fontWeight: '500' },
  cardBottomRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  cardTime: { fontSize: 11 },
  cardTotal: { fontSize: 15, fontWeight: '800' },
  cardActionRow: { marginTop: 10, paddingTop: 10, borderTopWidth: 1, borderTopColor: '#F5F3ED' },
  statusTransitionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    borderRadius: 14,
    gap: 8,
  },
  statusTransitionText: { color: '#FFFFFF', fontSize: 13, fontWeight: '700' },
  completedBadgeWrap: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, paddingVertical: 6 },
  completedBadgeText: { color: '#059669', fontSize: 12, fontWeight: '700' },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  orderDetailCard: { borderTopLeftRadius: 28, borderTopRightRadius: 28, padding: 20, maxHeight: '88%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  modalTitle: { fontSize: 18, fontWeight: '800' },
  modalSub: { fontSize: 12, marginTop: 2 },
  modalScroll: { maxHeight: 420 },
  infoBox: { padding: 12, borderRadius: 14, marginBottom: 14 },
  infoLabel: { fontSize: 13, fontWeight: '700', marginBottom: 4 },
  infoValue: { fontSize: 13, marginTop: 2 },
  sectionHeading: { fontSize: 14, fontWeight: '700', marginVertical: 10 },
  itemLine: { flexDirection: 'row', alignItems: 'center', paddingVertical: 8, borderBottomWidth: 1 },
  itemQtyBadge: { paddingHorizontal: 6, paddingVertical: 3, borderRadius: 6, fontSize: 11, fontWeight: '700' },
  itemName: { fontSize: 14, fontWeight: '600' },
  itemSize: { fontSize: 11 },
  itemPrice: { fontSize: 14, fontWeight: '700' },
  receiptBox: { padding: 14, borderRadius: 14, marginVertical: 14 },
  receiptLine: { flexDirection: 'row', justifyContent: 'space-between', marginVertical: 3 },
  receiptGrandLabel: { fontSize: 15, fontWeight: '800' },
  receiptGrandVal: { fontSize: 17, fontWeight: '800' },
  actionsWrap: { gap: 8, marginBottom: 20 },
  riderPickCard: { flexDirection: 'row', alignItems: 'center', padding: 12, borderRadius: 14, borderWidth: 1, marginBottom: 10 },
  riderIconCircle: { width: 38, height: 38, borderRadius: 19, alignItems: 'center', justifyContent: 'center' },
  riderPickName: { fontSize: 14, fontWeight: '700' },
  riderPickPhone: { fontSize: 12, marginTop: 2 },
  assignPill: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 10 },
  assignPillText: { color: '#FFFFFF', fontSize: 11, fontWeight: '700' },
  selfPickupBtn: { paddingVertical: 12, borderRadius: 12, alignItems: 'center', marginTop: 8 },
  selfPickupText: { fontSize: 13, fontWeight: '700' },
});
