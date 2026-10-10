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
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState, EmptyState } from '../../components/FeedbackStates';
import { StatusBadge } from '../../components/StatusBadge';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';

export default function DeliveryScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [selectedOrder, setSelectedOrder] = useState<any | null>(null);

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['active-deliveries'],
    queryFn: () => apiClient<any>('/delivery'),
    refetchInterval: 12000,
  });

  const assignRiderMutation = useMutation({
    mutationFn: ({ orderId, riderId }: { orderId: number; riderId: number }) =>
      apiClient(`/delivery/orders/${orderId}/assign`, {
        method: 'POST',
        body: JSON.stringify({ rider_id: riderId }),
      }),
    onSuccess: () => {
      setSelectedOrder(null);
      Alert.alert('Rider Assigned', 'Rider assigned. Delivery tracking link dispatched to rider.');
      queryClient.invalidateQueries({ queryKey: ['active-deliveries'] });
      queryClient.invalidateQueries({ queryKey: ['orders-pipeline'] });
      queryClient.invalidateQueries({ queryKey: ['command-center'] });
    },
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
      const url = Platform.select({
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
    const slipText = `🛵 *Delivery Slip - Order #${item.daily_order_number || item.id}*\n`
      + `👤 Customer: ${item.customer_name}\n`
      + `📞 Phone: ${item.customer_phone}\n`
      + `📍 Address: ${item.delivery_address}\n`
      + `💵 Bill to Collect: Rs. ${Number(item.total).toLocaleString()} (${item.payment_method?.toUpperCase() || 'COD'})\n`
      + `⏱️ Deliver safely!`;
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
          <View style={{ width: 36 }} />
        </View>
        <Text style={[styles.sub, { color: theme.textMuted }]}>
          Assign riders, send slips via WhatsApp, and open GPS navigation
        </Text>
      </View>

      {activeDeliveries.length === 0 ? (
        <EmptyState
          title="No deliveries awaiting dispatch"
          description="Confirmed delivery orders requiring rider dispatch will appear here."
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

              {/* Delivery Address with 1-Tap Google Maps Navigation (Feature 6) */}
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
                    onPress={() => setSelectedOrder(item)}
                    style={[styles.assignPillBtn, { backgroundColor: theme.primary }]}
                  >
                    <Text style={styles.assignPillText}>{item.rider_name ? 'Change' : 'Assign'}</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          )}
        />
      )}

      {/* Select Rider Modal */}
      {selectedOrder && (
        <Modal visible={!!selectedOrder} animationType="slide" transparent>
          <View style={styles.modalBg}>
            <View style={[styles.modalCard, { backgroundColor: theme.surface }]}>
              <View style={styles.modalHeader}>
                <Text style={[styles.modalTitle, { color: theme.text }]}>
                  Assign Rider for Order #{selectedOrder.daily_order_number || selectedOrder.id}
                </Text>
                <TouchableOpacity onPress={() => setSelectedOrder(null)}>
                  <Ionicons name="close-circle" size={26} color={theme.textMuted} />
                </TouchableOpacity>
              </View>

              {riders.length === 0 ? (
                <Text style={[styles.noRiders, { color: theme.textMuted }]}>
                  No active fleet riders found.
                </Text>
              ) : (
                <FlatList
                  data={riders}
                  keyExtractor={(r) => String(r.id)}
                  renderItem={({ item: rider }) => (
                    <TouchableOpacity
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
                          {rider.phone} • {rider.status?.toUpperCase() || 'AVAILABLE'}
                        </Text>
                      </View>
                      <Ionicons name="chevron-forward" size={18} color={theme.primary} />
                    </TouchableOpacity>
                  )}
                />
              )}
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
  title: { fontSize: 20, fontWeight: '800' },
  sub: { fontSize: 12, marginTop: 4 },
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
  modalBg: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    padding: 20,
    maxHeight: '65%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  modalTitle: { fontSize: 17, fontWeight: '800' },
  noRiders: { fontSize: 13, textAlign: 'center', marginVertical: 20 },
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
