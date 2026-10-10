import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState, EmptyState } from '../../components/FeedbackStates';
import { Ionicons } from '@expo/vector-icons';

interface OrderItem {
  id: number;
  name: string;
  quantity: number;
  notes?: string;
}

interface KitchenTicket {
  id: number;
  daily_order_number?: number;
  status: string;
  created_at: string;
  table_number?: string;
  delivery_type: string;
  customer_name?: string;
  items: OrderItem[];
}

export default function KitchenScreen() {
  const { theme } = useAppTheme();
  const queryClient = useQueryClient();
  const [filter, setFilter] = useState<'all' | 'preparing' | 'ready'>('all');
  const [completedItemIds, setCompletedItemIds] = useState<Record<number, boolean>>({});
  const [isManualRefreshing, setIsManualRefreshing] = useState(false);

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['kitchen-tickets'],
    queryFn: () => apiClient<any>('/orders?status=live'),
    refetchInterval: 8000,
  });

  const handleManualRefresh = async () => {
    setIsManualRefreshing(true);
    await refetch();
    setIsManualRefreshing(false);
  };

  const updateStatusMutation = useMutation({
    mutationFn: ({ orderId, status }: { orderId: number; status: string }) =>
      apiClient(`/orders/${orderId}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kitchen-tickets'] });
      queryClient.invalidateQueries({ queryKey: ['orders-pipeline'] });
      queryClient.invalidateQueries({ queryKey: ['command-center'] });
    },
    onError: (err: any) => {
      Alert.alert('Error', err.message || 'Could not update order status.');
    },
  });

  if (isLoading && !isRefetching) {
    return <LoadingState message="Connecting to Kitchen Display..." />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => refetch()} />;
  }

  const allOrders: KitchenTicket[] = data?.orders ?? [];
  const kitchenTickets = allOrders.filter((ord) => {
    if (filter === 'preparing') return ord.status === 'preparing';
    if (filter === 'ready') return ord.status === 'ready';
    return ord.status === 'confirmed' || ord.status === 'preparing' || ord.status === 'ready';
  });

  const toggleItemDone = (itemId: number) => {
    setCompletedItemIds((prev) => ({
      ...prev,
      [itemId]: !prev[itemId],
    }));
  };

  const getElapsedTime = (isoString: string) => {
    const start = new Date(isoString).getTime();
    const now = Date.now();
    const diffMins = Math.max(0, Math.floor((now - start) / 60000));
    return `${diffMins}m ago`;
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Kitchen Header matching Handoff Page 8 ── */}
      <View style={[styles.header, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
        <View style={styles.headerTop}>
          <View>
            <Text style={[styles.title, { color: theme.text }]}>Kitchen Display System</Text>
            <Text style={[styles.subtitle, { color: theme.textMuted }]}>
              Live line tickets & food preparation
            </Text>
          </View>
          <View style={[styles.activeCounter, { backgroundColor: theme.primaryLight }]}>
            <Text style={[styles.counterText, { color: theme.primary }]}>
              {kitchenTickets.length} Active
            </Text>
          </View>
        </View>

        {/* Filter Pills */}
        <View style={styles.pillRow}>
          {(['all', 'preparing', 'ready'] as const).map((tab) => {
            const active = filter === tab;
            const label = tab === 'all' ? 'All Tickets' : tab === 'preparing' ? 'In Oven / Prep' : 'Ready to Dispatch';
            return (
              <TouchableOpacity
                key={tab}
                onPress={() => setFilter(tab)}
                style={[
                  styles.filterPill,
                  active
                    ? { backgroundColor: theme.primary }
                    : { backgroundColor: theme.surfaceSubtle },
                ]}
              >
                <Text
                  style={[
                    styles.pillText,
                    { color: active ? '#FFFFFF' : theme.textMuted },
                    active && { fontWeight: '700' },
                  ]}
                >
                  {label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>
      </View>

      {/* ── Tickets List ── */}
      <ScrollView
        contentContainerStyle={styles.scrollList}
        refreshControl={<RefreshControl refreshing={isManualRefreshing} onRefresh={handleManualRefresh} />}
      >
        {kitchenTickets.length === 0 ? (
          <EmptyState
            title="Kitchen is all clear"
            description="No active food tickets waiting in the kitchen queue right now."
            actionTitle="Refresh Tickets"
            onAction={() => refetch()}
          />
        ) : (
          kitchenTickets.map((ticket) => {
            const isPrep = ticket.status === 'preparing';
            const isReady = ticket.status === 'ready';

            return (
              <View
                key={ticket.id}
                style={[
                  styles.ticketCard,
                  { backgroundColor: theme.surface, borderColor: theme.border },
                ]}
              >
                {/* Ticket Top Banner */}
                <View style={styles.ticketTopRow}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                    <Text style={[styles.ticketNumber, { color: theme.text }]}>
                      #{ticket.daily_order_number || ticket.id}
                    </Text>
                    <View
                      style={[
                        styles.typeBadge,
                        {
                          backgroundColor:
                            ticket.delivery_type === 'dine_in'
                              ? '#E0F2FE'
                              : ticket.delivery_type === 'delivery'
                              ? '#FEF3C7'
                              : '#F1F5F9',
                        },
                      ]}
                    >
                      <Text
                        style={[
                          styles.typeBadgeText,
                          {
                            color:
                              ticket.delivery_type === 'dine_in'
                                ? '#0369A1'
                                : ticket.delivery_type === 'delivery'
                                ? '#B45309'
                                : '#475569',
                          },
                        ]}
                      >
                        {ticket.delivery_type?.toUpperCase() || 'ORDER'}
                      </Text>
                    </View>
                  </View>

                  <View style={styles.timeTag}>
                    <Ionicons name="time-outline" size={14} color={theme.textMuted} />
                    <Text style={[styles.timeText, { color: theme.textMuted }]}>
                      {getElapsedTime(ticket.created_at)}
                    </Text>
                  </View>
                </View>

                {ticket.customer_name ? (
                  <Text style={[styles.customerLine, { color: theme.textMuted }]}>
                    Customer: {ticket.customer_name}
                  </Text>
                ) : null}

                {/* Items with Checklist */}
                <View style={[styles.itemsDivider, { borderTopColor: theme.border }]} />
                <View style={styles.itemsList}>
                  {ticket.items?.map((item) => {
                    const isDone = !!completedItemIds[item.id];
                    return (
                      <TouchableOpacity
                        key={item.id}
                        activeOpacity={0.7}
                        onPress={() => toggleItemDone(item.id)}
                        style={styles.itemRow}
                      >
                        <Ionicons
                          name={isDone ? 'checkbox' : 'square-outline'}
                          size={22}
                          color={isDone ? theme.primary : theme.textMuted}
                        />
                        <Text
                          style={[
                            styles.itemQty,
                            { backgroundColor: theme.surfaceSubtle, color: theme.text },
                          ]}
                        >
                          x{item.quantity}
                        </Text>
                        <Text
                          style={[
                            styles.itemName,
                            {
                              color: isDone ? theme.textMuted : theme.text,
                              textDecorationLine: isDone ? 'line-through' : 'none',
                            },
                          ]}
                        >
                          {item.name}
                        </Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>

                {/* Ticket Action Button */}
                <View style={[styles.actionRow, { borderTopColor: theme.border }]}>
                  {ticket.status === 'confirmed' && (
                    <TouchableOpacity
                      onPress={() =>
                        updateStatusMutation.mutate({ orderId: ticket.id, status: 'preparing' })
                      }
                      style={[styles.btn, { backgroundColor: theme.primary }]}
                    >
                      <Ionicons name="flame" size={16} color="#FFFFFF" />
                      <Text style={styles.btnText}>Start Preparing</Text>
                    </TouchableOpacity>
                  )}

                  {ticket.status === 'preparing' && (
                    <TouchableOpacity
                      onPress={() =>
                        updateStatusMutation.mutate({ orderId: ticket.id, status: 'ready' })
                      }
                      style={[styles.btn, { backgroundColor: '#22C55E' }]}
                    >
                      <Ionicons name="checkmark-done" size={16} color="#FFFFFF" />
                      <Text style={styles.btnText}>Mark Food Ready</Text>
                    </TouchableOpacity>
                  )}

                  {ticket.status === 'ready' && (
                    <View style={styles.readyIndicator}>
                      <Ionicons name="checkmark-circle" size={18} color="#22C55E" />
                      <Text style={styles.readyText}>Ready for Counter / Dispatch</Text>
                    </View>
                  )}
                </View>
              </View>
            );
          })
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: { paddingHorizontal: 16, paddingTop: 10, paddingBottom: 14, borderBottomWidth: 1 },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  title: { fontSize: 22, fontWeight: '800', letterSpacing: -0.3 },
  subtitle: { fontSize: 12, marginTop: 2 },
  activeCounter: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20 },
  counterText: { fontSize: 12, fontWeight: '700' },
  pillRow: { flexDirection: 'row', gap: 8 },
  filterPill: { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20 },
  pillText: { fontSize: 12, fontWeight: '600' },
  scrollList: { padding: 16, paddingBottom: 90 },
  ticketCard: { borderRadius: 18, borderWidth: 1, padding: 16, marginBottom: 14, elevation: 1 },
  ticketTopRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  ticketNumber: { fontSize: 18, fontWeight: '800' },
  typeBadge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6 },
  typeBadgeText: { fontSize: 11, fontWeight: '700' },
  timeTag: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  timeText: { fontSize: 12, fontWeight: '500' },
  customerLine: { fontSize: 12, marginTop: 4 },
  itemsDivider: { borderTopWidth: 1, marginVertical: 12 },
  itemsList: { gap: 10 },
  itemRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  itemQty: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6, fontSize: 12, fontWeight: '700' },
  itemName: { fontSize: 14, fontWeight: '600', flex: 1 },
  actionRow: { marginTop: 14, paddingTop: 12, borderTopWidth: 1 },
  btn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    height: 44,
    borderRadius: 12,
    gap: 8,
  },
  btnText: { color: '#FFFFFF', fontSize: 14, fontWeight: '700' },
  readyIndicator: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, paddingVertical: 8 },
  readyText: { color: '#22C55E', fontSize: 13, fontWeight: '700' },
});
