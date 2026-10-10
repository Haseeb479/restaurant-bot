import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  Modal,
  Alert,
  Linking,
  Platform,
  ScrollView,
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

export default function DeliveryScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
  const queryClient = useQueryClient();

  const [selectedOrder, setSelectedOrder] = useState<any | null>(null);
  const [isAddingRider, setIsAddingRider] = useState(false);
  const [newRiderName, setNewRiderName] = useState('');
  const [newRiderPhone, setNewRiderPhone] = useState('');

  // Quick manual rider dispatch
  const [showManualDispatch, setShowManualDispatch] = useState(false);
  const [customRiderName, setCustomRiderName] = useState('');
  const [customRiderPhone, setCustomRiderPhone] = useState('');

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['active-deliveries'],
    queryFn: () => apiClient<any>('/delivery'),
    refetchInterval: 10000,
  });

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
      setSelectedOrder(null);
      setCustomRiderName('');
      setCustomRiderPhone('');
      setShowManualDispatch(false);
      Alert.alert('Rider Assigned 🛵', res.message || 'Rider assigned & dispatched for delivery.');
      queryClient.invalidateQueries({ queryKey: ['active-deliveries'] });
      queryClient.invalidateQueries({ queryKey: ['orders-pipeline'] });
      queryClient.invalidateQueries({ queryKey: ['delivery-riders'] });
    },
    onError: (err: any) => Alert.alert('Assignment Error', err.message || 'Failed to assign rider.'),
  });

  const addRiderMutation = useMutation({
    mutationFn: ({ name, phone }: { name: string; phone: string }) =>
      apiClient('/delivery/riders', {
        method: 'POST',
        body: JSON.stringify({ name, phone }),
      }),
    onSuccess: (res: any) => {
      setIsAddingRider(false);
      setNewRiderName('');
      setNewRiderPhone('');
      queryClient.invalidateQueries({ queryKey: ['active-deliveries'] });
      queryClient.invalidateQueries({ queryKey: ['delivery-riders'] });
      Alert.alert('Rider Added 🎉', res.message || 'New rider added to fleet.');
    },
    onError: (err: any) => Alert.alert('Error', err.message || 'Failed to add rider.'),
  });

  // Call rider phone directly via GSM
  const handleCallRider = (phone?: string) => {
    if (!phone) {
      Alert.alert('No Phone', 'Rider phone number not configured.');
      return;
    }
    Linking.openURL(`tel:${phone}`);
  };

  // Open Google Maps navigation directly to customer coordinates
  const handleOpenNavigation = (address: string, lat?: number, lng?: number) => {
    if (lat && lng) {
      const url =
        Platform.select({
          ios: `maps:0,0?q=${lat},${lng}`,
          android: `geo:0,0?q=${lat},${lng}`,
        }) || `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
      Linking.openURL(url);
    } else if (address) {
      const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
      Linking.openURL(url);
    } else {
      Alert.alert('No Address', 'Customer location address not provided.');
    }
  };

  // Send WhatsApp slip to rider
  const handleSendSlipToRider = (item: any) => {
    if (!item.rider_phone) {
      Alert.alert('No Rider Phone', 'Please assign a rider with a valid phone number.');
      return;
    }
    const cleanPhone = item.rider_phone.replace(/[^0-9]/g, '');
    const slipText =
      `🛵 *Delivery Slip - Order #${item.daily_order_number || item.id}*\n` +
      `👤 Customer: ${item.customer_name}\n` +
      `📞 Phone: ${item.customer_phone}\n` +
      `📍 Address: ${item.delivery_address}\n` +
      `💵 Bill to Collect: Rs. ${Number(item.total).toLocaleString()} (${item.payment_method?.toUpperCase() || 'COD'})\n` +
      `⏱️ Deliver safely!`;
    Linking.openURL(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(slipText)}`);
  };

  if (isLoading && !isRefetching) return <LoadingState message="Loading dispatches..." />;
  if (error) return <ErrorState message={error.message} onRetry={() => refetch()} />;

  const activeDeliveries = data?.active_deliveries ?? [];
  const riders = data?.riders ?? [];

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* Header */}
      <View style={[styles.header, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
        <View style={styles.topRow}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color={theme.text} />
          </TouchableOpacity>
          <Text style={[styles.title, { color: theme.text }]}>Delivery Dispatch & Fleet</Text>
          <TouchableOpacity
            onPress={() => setIsAddingRider(true)}
            style={[styles.addRiderHeaderBtn, { backgroundColor: theme.primary }]}
          >
            <Ionicons name="person-add" size={16} color="#FFFFFF" />
            <Text style={styles.addRiderBtnText}>+ Rider</Text>
          </TouchableOpacity>
        </View>
        <Text style={[styles.sub, { color: theme.textMuted }]}>
          Fleet size: {riders.length} rider(s) • {activeDeliveries.length} active delivery order(s)
        </Text>
      </View>

      {/* Fleet Roster Quick Carousel / Pills if riders exist */}
      {riders.length > 0 && (
        <View style={[styles.fleetStrip, { backgroundColor: theme.surfaceSubtle }]}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8, paddingHorizontal: 16 }}>
            {riders.map((r: any) => (
              <View
                key={r.id}
                style={[styles.fleetRiderPill, { backgroundColor: theme.surface, borderColor: theme.border }]}
              >
                <Ionicons name="bicycle" size={14} color={theme.primary} />
                <Text style={[styles.fleetRiderName, { color: theme.text }]}>{r.name}</Text>
                {r.phone ? (
                  <TouchableOpacity onPress={() => handleCallRider(r.phone)}>
                    <Ionicons name="call" size={13} color="#16A34A" />
                  </TouchableOpacity>
                ) : null}
              </View>
            ))}
          </ScrollView>
        </View>
      )}

      {activeDeliveries.length === 0 ? (
        <EmptyState
          title="No deliveries awaiting dispatch"
          description="Confirmed delivery orders requiring rider dispatch will appear here automatically."
        />
      ) : (
        <FlatList
          data={activeDeliveries}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.listPadding}
          refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
          renderItem={({ item }) => (
            <View
              style={[styles.deliveryCard, { backgroundColor: theme.surface, borderColor: theme.border }]}
            >
              <View style={styles.cardTop}>
                <View>
                  <Text style={[styles.orderNumber, { color: theme.text }]}>
                    {item.daily_order_number ? `#${item.daily_order_number}` : `#${item.id}`}
                  </Text>
                  <Text style={[styles.customerText, { color: theme.textMuted }]}>
                    {item.customer_name} • {item.customer_phone}
                  </Text>
                </View>
                <StatusBadge status={item.status} />
              </View>

              {/* Delivery Address with 1-Tap Google Maps Navigation */}
              <TouchableOpacity
                onPress={() => handleOpenNavigation(item.delivery_address, item.delivery_lat, item.delivery_lng)}
                style={[styles.addressPill, { backgroundColor: theme.surfaceSubtle }]}
              >
                <Ionicons name="navigate-circle" size={20} color={theme.primary} />
                <Text style={[styles.addressText, { color: theme.text }]} numberOfLines={2}>
                  {item.delivery_address || 'Customer location pin'}
                </Text>
                <Text style={[styles.navHint, { color: theme.primary }]}>Maps →</Text>
              </TouchableOpacity>

              {/* Rider Row with Actions */}
              <View style={[styles.riderRow, { borderTopColor: theme.border }]}>
                <View style={styles.riderInfo}>
                  <Ionicons name="bicycle" size={20} color={item.rider_name ? '#16A34A' : theme.textMuted} />
                  <View>
                    <Text style={[styles.riderName, { color: theme.text }]}>
                      {item.rider_name || 'Unassigned Rider'}
                    </Text>
                    {item.rider_phone ? (
                      <Text style={[styles.riderPhone, { color: theme.textMuted }]}>{item.rider_phone}</Text>
                    ) : null}
                  </View>
                </View>

                <View style={styles.riderActionGroup}>
                  {item.rider_name ? (
                    <>
                      <TouchableOpacity
                        onPress={() => handleCallRider(item.rider_phone)}
                        style={[styles.miniActionBtn, { backgroundColor: '#F0FDF4' }]}
                      >
                        <Ionicons name="call" size={16} color="#16A34A" />
                      </TouchableOpacity>

                      <TouchableOpacity
                        onPress={() => handleSendSlipToRider(item)}
                        style={[styles.miniActionBtn, { backgroundColor: theme.primaryLight }]}
                      >
                        <Ionicons name="logo-whatsapp" size={16} color={theme.primary} />
                      </TouchableOpacity>
                    </>
                  ) : null}

                  <TouchableOpacity
                    onPress={() => {
                      setSelectedOrder(item);
                      setShowManualDispatch(false);
                      setCustomRiderName('');
                      setCustomRiderPhone('');
                    }}
                    style={[styles.assignPillBtn, { backgroundColor: theme.primary }]}
                  >
                    <Text style={styles.assignPillText}>{item.rider_name ? 'Change Rider' : 'Assign Rider'}</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          )}
        />
      )}

      {/* ── Assign Rider Modal ── */}
      {selectedOrder && (
        <Modal visible={!!selectedOrder} animationType="slide" transparent onRequestClose={() => setSelectedOrder(null)}>
          <View style={styles.modalBg}>
            <View style={[styles.modalCard, { backgroundColor: theme.surface }]}>
              <View style={styles.modalHeader}>
                <View>
                  <Text style={[styles.modalTitle, { color: theme.text }]}>
                    Assign Rider for #{selectedOrder.daily_order_number || selectedOrder.id}
                  </Text>
                  <Text style={{ fontSize: 12, color: theme.textMuted, marginTop: 2 }}>
                    📍 {selectedOrder.delivery_address}
                  </Text>
                </View>
                <TouchableOpacity onPress={() => setSelectedOrder(null)}>
                  <Ionicons name="close-circle" size={26} color={theme.textMuted} />
                </TouchableOpacity>
              </View>

              <ScrollView style={{ maxHeight: 380 }}>
                {riders.length === 0 ? (
                  <View style={{ padding: 12, borderRadius: 10, backgroundColor: theme.surfaceSubtle, marginBottom: 12 }}>
                    <Text style={[styles.noRiders, { color: theme.textMuted }]}>
                      No active fleet riders found. Type rider details below or tap "+ Add Rider".
                    </Text>
                  </View>
                ) : (
                  riders.map((rider: any) => (
                    <TouchableOpacity
                      key={rider.id}
                      onPress={() =>
                        assignRiderMutation.mutate({
                          orderId: selectedOrder.id,
                          riderId: rider.id,
                        })
                      }
                      style={[styles.riderOption, { borderBottomColor: theme.border }]}
                    >
                      <View>
                        <Text style={[styles.optName, { color: theme.text }]}>{rider.name}</Text>
                        <Text style={[styles.optPhone, { color: theme.textMuted }]}>
                          {rider.phone} • AVAILABLE
                        </Text>
                      </View>
                      <View style={[styles.assignBtnSmall, { backgroundColor: theme.primary }]}>
                        <Text style={{ color: '#FFFFFF', fontSize: 11, fontWeight: '700' }}>Assign</Text>
                      </View>
                    </TouchableOpacity>
                  ))
                )}

                {/* Quick Manual Entry Option */}
                <TouchableOpacity
                  onPress={() => setShowManualDispatch(!showManualDispatch)}
                  style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingVertical: 12 }}
                >
                  <Text style={{ fontSize: 13, fontWeight: '700', color: theme.primary }}>
                    {showManualDispatch ? '▲ Hide Quick Rider Entry' : '▼ + Dispatch by Custom Name & Phone'}
                  </Text>
                </TouchableOpacity>

                {showManualDispatch && (
                  <View style={{ backgroundColor: theme.surfaceSubtle, padding: 12, borderRadius: 12, marginBottom: 12, gap: 8 }}>
                    <AppInput
                      label="Rider Name"
                      placeholder="e.g. Imran Khan"
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
                      title="Dispatch Order to Rider 🛵"
                      loading={assignRiderMutation.isPending}
                      onPress={() => {
                        if (!customRiderName.trim()) {
                          Alert.alert('Required', 'Please enter rider name.');
                          return;
                        }
                        assignRiderMutation.mutate({
                          orderId: selectedOrder.id,
                          riderName: customRiderName.trim(),
                          riderPhone: customRiderPhone.trim(),
                        });
                      }}
                    />
                  </View>
                )}
              </ScrollView>
            </View>
          </View>
        </Modal>
      )}

      {/* ── Add Fleet Rider Modal ── */}
      {isAddingRider && (
        <Modal visible={isAddingRider} animationType="slide" transparent onRequestClose={() => setIsAddingRider(false)}>
          <View style={styles.modalBg}>
            <View style={[styles.modalCard, { backgroundColor: theme.surface }]}>
              <View style={styles.modalHeader}>
                <Text style={[styles.modalTitle, { color: theme.text }]}>Add New Fleet Rider</Text>
                <TouchableOpacity onPress={() => setIsAddingRider(false)}>
                  <Ionicons name="close-circle" size={26} color={theme.textMuted} />
                </TouchableOpacity>
              </View>

              <View style={{ gap: 12, paddingBottom: 10 }}>
                <AppInput
                  label="Rider Full Name"
                  placeholder="e.g. Muhammad Bilal"
                  value={newRiderName}
                  onChangeText={setNewRiderName}
                />
                <AppInput
                  label="WhatsApp / Mobile Phone"
                  placeholder="e.g. 03001234567"
                  value={newRiderPhone}
                  onChangeText={setNewRiderPhone}
                  keyboardType="phone-pad"
                />
                <AppButton
                  title="Save to Fleet 🚴"
                  loading={addRiderMutation.isPending}
                  onPress={() => {
                    if (!newRiderName.trim()) {
                      Alert.alert('Required', 'Please enter rider full name.');
                      return;
                    }
                    if (!newRiderPhone.trim()) {
                      Alert.alert('Required', 'Please enter rider phone number.');
                      return;
                    }
                    addRiderMutation.mutate({
                      name: newRiderName.trim(),
                      phone: newRiderPhone.trim(),
                    });
                  }}
                  style={{ marginTop: 8 }}
                />
              </View>
            </View>
          </View>
        </Modal>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: { paddingHorizontal: 16, paddingTop: 10, paddingBottom: 14, borderBottomWidth: 1 },
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  backBtn: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  title: { fontSize: 18, fontWeight: '800' },
  sub: { fontSize: 12, marginTop: 4 },
  addRiderHeaderBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 10,
  },
  addRiderBtnText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700' },
  fleetStrip: { paddingVertical: 8 },
  fleetRiderPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 12,
    borderWidth: 1,
  },
  fleetRiderName: { fontSize: 12, fontWeight: '700' },
  listPadding: { padding: 16, paddingBottom: 90 },
  deliveryCard: {
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 12,
  },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  orderNumber: { fontSize: 16, fontWeight: '800' },
  customerText: { fontSize: 12, marginTop: 2 },
  addressPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    padding: 10,
    borderRadius: 12,
    marginBottom: 10,
  },
  addressText: { fontSize: 12, flex: 1, fontWeight: '600' },
  navHint: { fontSize: 12, fontWeight: '700' },
  riderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    paddingTop: 10,
  },
  riderInfo: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  riderName: { fontSize: 13, fontWeight: '700' },
  riderPhone: { fontSize: 11, marginTop: 1 },
  riderActionGroup: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  miniActionBtn: { width: 34, height: 34, borderRadius: 17, alignItems: 'center', justifyContent: 'center' },
  assignPillBtn: { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 14 },
  assignPillText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700' },
  assignBtnSmall: { paddingHorizontal: 10, paddingVertical: 5, borderRadius: 8 },
  modalBg: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    padding: 20,
    maxHeight: '75%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  modalTitle: { fontSize: 17, fontWeight: '800' },
  noRiders: { fontSize: 12, textAlign: 'center', marginVertical: 10 },
  riderOption: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  optName: { fontSize: 15, fontWeight: '700' },
  optPhone: { fontSize: 12, marginTop: 2 },
});
