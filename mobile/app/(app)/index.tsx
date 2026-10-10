import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Switch,
  TouchableOpacity,
  Modal,
  Image,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { StatusBadge } from '../../components/StatusBadge';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';

export default function DashboardScreen() {
  const { theme } = useAppTheme();
  const queryClient = useQueryClient();
  const router = useRouter();

  const [isQrModalOpen, setIsQrModalOpen] = useState(false);
  const [qrCodeData, setQrCodeData] = useState<string | null>(null);
  const [pairingCode, setPairingCode] = useState<string | null>(null);
  const [isManualRefreshing, setIsManualRefreshing] = useState(false);

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['command-center'],
    queryFn: () => apiClient<any>('/dashboard/command-center'),
    refetchInterval: 12000,
  });

  const { data: profileData, refetch: refetchProfile } = useQuery({
    queryKey: ['owner-profile'],
    queryFn: () => apiClient<any>('/profile'),
    refetchInterval: 15000,
  });

  const handleManualRefresh = async () => {
    setIsManualRefreshing(true);
    await Promise.all([refetch(), refetchProfile()]);
    setIsManualRefreshing(false);
  };

  const toggleMutation = useMutation({
    mutationFn: () => apiClient<any>('/dashboard/toggle-open', { method: 'POST' }),
    onSuccess: (res: any) => {
      queryClient.invalidateQueries({ queryKey: ['command-center'] });
      queryClient.invalidateQueries({ queryKey: ['owner-profile'] });
      Alert.alert(
        'Store Status Updated',
        res.is_open ? 'Store is OPEN. WhatsApp Bot is accepting customer orders.' : 'Store is CLOSED. WhatsApp Bot will inform customers that orders are paused.'
      );
    },
  });

  const restartBotMutation = useMutation({
    mutationFn: () => apiClient<any>('/profile/bot-restart', { method: 'POST' }),
    onSuccess: () => {
      Alert.alert('Bot Restarted', 'WhatsApp AI engine restart command dispatched.');
      queryClient.invalidateQueries({ queryKey: ['owner-profile'] });
    },
    onError: (err: any) => Alert.alert('Restart Failed', err.message || 'Could not restart bot.'),
  });

  const handleFetchQr = async () => {
    try {
      const res = await apiClient<any>('/profile/bot-qr');
      if (res.success && res.qr) {
        setQrCodeData(res.qr);
        setPairingCode(res.code || null);
        setIsQrModalOpen(true);
      } else {
        Alert.alert('Pairing Status', res.message || 'Bot is already connected or initializing.');
      }
    } catch (e: any) {
      Alert.alert('Error', e.message || 'Could not retrieve QR code.');
    }
  };

  if (isLoading && !isRefetching) {
    return <LoadingState message="Loading Restaurant Dashboard..." />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => refetch()} />;
  }

  const kpis = data?.kpis ?? {};
  const attention = data?.needs_attention ?? {};
  const recentOrders = data?.recent_orders ?? [];
  const isOpen = data?.restaurant?.is_open ?? false;
  const botStatus = profileData?.profile?.bot_status || data?.restaurant?.bot_status || 'connected';
  const isBotConnected =
    botStatus === 'connected' ||
    botStatus === 'open' ||
    data?.restaurant?.bot_status === 'connected' ||
    profileData?.profile?.bot_status === 'connected';

  const pendingCount = Number(attention.pending_orders ?? 0);
  const inKitchenCount = Number(attention.in_kitchen ?? 0);
  const deliveryIssuesCount = Number(
    attention.unassigned_deliveries ?? attention.delivery_issues ?? attention.waiting_for_rider ?? 0
  );
  const unavailableCount = Number(attention.unavailable_items ?? 0);
  const isBotDisconnected = attention.bot_disconnected === true || !isBotConnected;
  const totalIssues =
    pendingCount + inKitchenCount + deliveryIssuesCount + (isBotDisconnected ? 1 : 0) + unavailableCount;

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Top Foodio Brand Bar ── */}
      <View style={[styles.topBar, { backgroundColor: theme.background }]}>
        <View style={styles.brandRow}>
          <Image
            source={require('../../assets/brand-symbol.png')}
            style={styles.brandIcon}
            resizeMode="contain"
          />
          <Text style={styles.brandLogoText}>foodio</Text>
        </View>

        <View style={styles.topActions}>
          <TouchableOpacity
            onPress={() => router.push('/more/reports')}
            style={[styles.headerIconBtn, { backgroundColor: '#FFFFFF' }]}
          >
            <Ionicons name="notifications-outline" size={20} color="#064E45" />
            {totalIssues > 0 && <View style={styles.notificationDot} />}
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/(app)/more')}
            style={styles.avatarPill}
          >
            <View style={styles.avatarInner}>
              <Text style={styles.avatarInitials}>
                {(data?.restaurant?.name || profileData?.restaurant?.name || 'R')[0].toUpperCase()}
              </Text>
            </View>
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isManualRefreshing} onRefresh={handleManualRefresh} />}
      >
        {/* ── Greeting & Restaurant Header ── */}
        <View style={styles.greetingSection}>
          <Text style={styles.greetingLabel}>Welcome back,</Text>
          <Text style={styles.ownerName}>
            {data?.restaurant?.owner_name || profileData?.profile?.name || data?.restaurant?.name || 'Restaurant Owner'} 👋
          </Text>

          {/* Restaurant Selector Pill & Online Status */}
          <View style={styles.restaurantStatusRow}>
            <View style={styles.restaurantPill}>
              <Ionicons name="storefront-outline" size={16} color="#064E45" />
              <Text style={styles.restaurantPillName} numberOfLines={1}>
                {data?.restaurant?.name || profileData?.restaurant?.name || 'My Restaurant'}
              </Text>
            </View>

            <TouchableOpacity
              onPress={() => toggleMutation.mutate()}
              activeOpacity={0.8}
              style={[
                styles.onlineBadgePill,
                { backgroundColor: isOpen ? '#E6F4EA' : '#FCE8E6' },
              ]}
            >
              <View
                style={[
                  styles.onlineDot,
                  { backgroundColor: isOpen ? '#1E8E3E' : '#D93025' },
                ]}
              />
              <Text
                style={[
                  styles.onlineBadgeText,
                  { color: isOpen ? '#1E8E3E' : '#D93025' },
                ]}
              >
                {isOpen ? 'Online' : 'Offline'}
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* ── Metric Summary Cards (2 Cards Row Matching Exact Image) ── */}
        <View style={styles.statsTwoCardsRow}>
          {/* Card 1: Today's Sales with Growth Badge */}
          <View style={styles.salesStatCard}>
            <Text style={styles.statCardLabel}>Today's Sales</Text>
            <Text style={styles.salesStatAmount}>
              Rs {Number(kpis.today_sales || 0).toLocaleString()}
            </Text>
            <View style={styles.statTrendRow}>
              <Ionicons name="stats-chart" size={13} color="#064E45" />
              <Text style={styles.statTrendText}>Live</Text>
            </View>
          </View>

          {/* Card 2: Total Orders & Avg. Order Split */}
          <View style={styles.secondaryStatCard}>
            <View style={styles.secondaryStatCol}>
              <Text style={styles.statCardLabel}>Total Orders</Text>
              <Text style={styles.secondaryStatVal}>{kpis.today_orders ?? 0}</Text>
            </View>

            <View style={styles.secondaryStatCol}>
              <Text style={styles.statCardLabel}>Avg. Order</Text>
              <Text style={styles.secondaryStatVal}>
                Rs {Number(kpis.aov || 0).toLocaleString()}
              </Text>
            </View>
          </View>
        </View>

        {/* ── Needs Attention Alert Card (Live Data Only) ── */}
        <View style={styles.attentionCard}>
          <View style={styles.attentionHeader}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
              <Ionicons
                name={totalIssues > 0 ? 'warning' : 'checkmark-circle'}
                size={18}
                color={totalIssues > 0 ? '#FF3B30' : '#1E8E3E'}
              />
              <Text style={styles.attentionTitle}>Needs Attention</Text>
            </View>
            <TouchableOpacity onPress={() => router.push('/(app)/orders')}>
              <Text style={styles.attentionViewAll}>View All &gt;</Text>
            </TouchableOpacity>
          </View>

          {totalIssues === 0 ? (
            <View style={{ paddingVertical: 12, flexDirection: 'row', alignItems: 'center', gap: 8 }}>
              <Ionicons name="shield-checkmark" size={20} color="#1E8E3E" />
              <Text style={{ fontSize: 13, color: '#112D27', fontWeight: '600' }}>
                All caught up! 0 issues requiring attention.
              </Text>
            </View>
          ) : (
            <View style={styles.attentionList}>
              {/* Item 1: New Orders */}
              {pendingCount > 0 && (
                <TouchableOpacity
                  onPress={() => router.push('/(app)/orders')}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#FF3B30' }]}>
                      <Text style={styles.attentionBadgeNum}>{pendingCount}</Text>
                    </View>
                    <Text style={styles.attentionText}>
                      {pendingCount} New Order{pendingCount > 1 ? 's' : ''}
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color="#C7C7CC" />
                </TouchableOpacity>
              )}

              {/* Item 2: Preparing */}
              {inKitchenCount > 0 && (
                <TouchableOpacity
                  onPress={() => router.push('/(app)/orders')}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#FF9500' }]}>
                      <Text style={styles.attentionBadgeNum}>{inKitchenCount}</Text>
                    </View>
                    <Text style={styles.attentionText}>
                      {inKitchenCount} In Kitchen / Preparing
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color="#C7C7CC" />
                </TouchableOpacity>
              )}

              {/* Item 3: Delivery Issue */}
              {deliveryIssuesCount > 0 && (
                <TouchableOpacity
                  onPress={() => router.push('/(app)/delivery')}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#007AFF' }]}>
                      <Text style={styles.attentionBadgeNum}>{deliveryIssuesCount}</Text>
                    </View>
                    <Text style={styles.attentionText}>
                      {deliveryIssuesCount} Unassigned Delivery{deliveryIssuesCount > 1 ? 's' : ''}
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color="#C7C7CC" />
                </TouchableOpacity>
              )}

              {/* Item 4: WhatsApp Bot Disconnected */}
              {isBotDisconnected && (
                <TouchableOpacity
                  onPress={handleFetchQr}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#FF3B30' }]}>
                      <Ionicons name="logo-whatsapp" size={11} color="#FFFFFF" />
                    </View>
                    <Text style={styles.attentionText}>WhatsApp Bot Disconnected</Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color="#C7C7CC" />
                </TouchableOpacity>
              )}

              {/* Item 5: Unavailable Items */}
              {unavailableCount > 0 && (
                <TouchableOpacity
                  onPress={() => router.push('/(app)/menu')}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#8E8E93' }]}>
                      <Text style={styles.attentionBadgeNum}>{unavailableCount}</Text>
                    </View>
                    <Text style={styles.attentionText}>
                      {unavailableCount} Sold Out / 86'd Item{unavailableCount > 1 ? 's' : ''}
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color="#C7C7CC" />
                </TouchableOpacity>
              )}
            </View>
          )}
        </View>

        {/* ── Live Orders Section Header ── */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Live Orders</Text>
          <TouchableOpacity onPress={() => router.push('/(app)/orders')}>
            <Text style={styles.sectionViewAll}>View All &gt;</Text>
          </TouchableOpacity>
        </View>

        {/* ── Live Orders List ── */}
        {recentOrders.length === 0 ? (
          <View style={styles.emptyCard}>
            <Ionicons name="receipt-outline" size={34} color="#7E9188" />
            <Text style={styles.emptyTitle}>No live orders right now</Text>
            <Text style={styles.emptySub}>
              Orders placed via WhatsApp or POS will appear here instantly.
            </Text>
          </View>
        ) : (
          recentOrders.slice(0, 5).map((ord: any) => {
            const isNew = ord.status === 'pending' || ord.status === 'confirmed';
            const isPrep = ord.status === 'preparing';
            const isDelivery = ord.status === 'out_for_delivery';

            return (
              <View key={ord.id} style={styles.liveOrderRow}>
                {/* Customer Avatar Thumbnail */}
                <View style={styles.orderAvatar}>
                  <Text style={styles.orderAvatarLetter}>
                    {(ord.customer_name || 'C')[0].toUpperCase()}
                  </Text>
                </View>

                {/* Middle details: ID & Price on left, Status badge */}
                <View style={{ flex: 1, marginLeft: 12 }}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                    <Text style={styles.liveOrderNumber}>
                      {ord.order_number ? `#${ord.order_number.replace('#', '')}` : `#${ord.id}`}
                    </Text>
                    {isNew && (
                      <View style={[styles.pillBadge, { backgroundColor: '#FFF2E8' }]}>
                        <Text style={[styles.pillBadgeText, { color: '#FF7A00' }]}>New</Text>
                      </View>
                    )}
                    {isPrep && (
                      <View style={[styles.pillBadge, { backgroundColor: '#FFF8E6' }]}>
                        <Text style={[styles.pillBadgeText, { color: '#E5A100' }]}>Preparing</Text>
                      </View>
                    )}
                    {isDelivery && (
                      <View style={[styles.pillBadge, { backgroundColor: '#EEF2FF' }]}>
                        <Text style={[styles.pillBadgeText, { color: '#4F46E5' }]}>Out for delivery</Text>
                      </View>
                    )}
                  </View>

                  <Text style={styles.liveOrderPrice}>
                    Rs {Number(ord.total).toLocaleString()}
                  </Text>
                </View>

                {/* Time & Counter details (Live from database) */}
                <View style={styles.orderMetaCol}>
                  <Text style={styles.metaItemsCount}>
                    {ord.items_count
                      ? `${ord.items_count} item${ord.items_count > 1 ? 's' : ''}`
                      : ord.items_summary
                      ? `${ord.items_summary.split(',').length} item${ord.items_summary.split(',').length > 1 ? 's' : ''}`
                      : '1 item'}
                  </Text>
                  <Text style={styles.metaTime}>{ord.time || ord.created_at || ''}</Text>
                </View>

                {/* Right Action Button */}
                {isNew ? (
                  <TouchableOpacity
                    onPress={() => router.push('/(app)/orders')}
                    style={styles.acceptPillBtn}
                  >
                    <Text style={styles.acceptPillBtnText}>Accept</Text>
                  </TouchableOpacity>
                ) : isDelivery ? (
                  <TouchableOpacity
                    onPress={() => router.push('/(app)/delivery')}
                    style={styles.trackPillBtn}
                  >
                    <Text style={styles.trackPillBtnText}>Track</Text>
                  </TouchableOpacity>
                ) : (
                  <TouchableOpacity
                    onPress={() => router.push('/(app)/orders')}
                    style={styles.viewPillBtn}
                  >
                    <Text style={styles.viewPillBtnText}>View</Text>
                  </TouchableOpacity>
                )}
              </View>
            );
          })
        )}
      </ScrollView>

      {/* ── Bot Pairing QR Code Modal ── */}
      {isQrModalOpen && (
        <Modal visible={isQrModalOpen} animationType="slide" transparent onRequestClose={() => setIsQrModalOpen(false)}>
          <View style={styles.modalBackdrop}>
            <View style={[styles.qrDialog, { backgroundColor: theme.surface }]}>
              <View style={styles.qrHeader}>
                <Text style={[styles.qrTitle, { color: theme.text }]}>WhatsApp Pairing</Text>
                <TouchableOpacity onPress={() => setIsQrModalOpen(false)}>
                  <Ionicons name="close-circle" size={26} color={theme.textMuted} />
                </TouchableOpacity>
              </View>

              <Text style={[styles.qrInstructions, { color: theme.textMuted }]}>
                Open WhatsApp on your phone &gt; Linked Devices &gt; Link a Device, then scan:
              </Text>

              {qrCodeData ? (
                <View style={styles.qrImageWrapper}>
                  <Image source={{ uri: qrCodeData }} style={styles.qrImage} resizeMode="contain" />
                </View>
              ) : null}

              {pairingCode ? (
                <View style={[styles.pairingCodeBox, { backgroundColor: theme.surfaceSubtle }]}>
                  <Text style={{ fontSize: 12, color: theme.textMuted }}>Pairing Code:</Text>
                  <Text style={[styles.pairingCodeText, { color: theme.primary }]}>{pairingCode}</Text>
                </View>
              ) : null}

              <TouchableOpacity
                onPress={() => setIsQrModalOpen(false)}
                style={[styles.closeModalBtn, { backgroundColor: theme.primary }]}
              >
                <Text style={styles.closeModalBtnText}>Close</Text>
              </TouchableOpacity>
            </View>
          </View>
        </Modal>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  topBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 10,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
  },
  brandIcon: {
    width: 22,
    height: 22,
  },
  brandLogoText: {
    fontSize: 22,
    fontWeight: '900',
    color: '#064E45',
    letterSpacing: -0.6,
  },
  topActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  headerIconBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#ECE9E0',
    position: 'relative',
  },
  notificationDot: {
    position: 'absolute',
    top: 9,
    right: 10,
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#EF4444',
  },
  avatarPill: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#E6F0EC',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#064E45',
  },
  avatarInner: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarInitials: {
    fontSize: 15,
    fontWeight: '800',
    color: '#064E45',
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingBottom: 100,
  },
  greetingSection: {
    marginTop: 6,
    marginBottom: 16,
  },
  greetingLabel: {
    fontSize: 13,
    color: '#7E9188',
    fontWeight: '500',
  },
  ownerName: {
    fontSize: 22,
    fontWeight: '800',
    color: '#112D27',
    letterSpacing: -0.4,
    marginTop: 2,
  },
  restaurantStatusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 10,
  },
  restaurantPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#ECE9E0',
  },
  restaurantPillName: {
    fontSize: 13,
    fontWeight: '700',
    color: '#112D27',
  },
  onlineBadgePill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 20,
  },
  onlineDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
  },
  onlineBadgeText: {
    fontSize: 12,
    fontWeight: '700',
  },
  statsTwoCardsRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 16,
  },
  salesStatCard: {
    flex: 1.1,
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    elevation: 2,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
  },
  statCardLabel: {
    fontSize: 11,
    color: '#7E9188',
    fontWeight: '500',
    marginBottom: 6,
  },
  salesStatAmount: {
    fontSize: 20,
    fontWeight: '800',
    color: '#112D27',
  },
  statTrendRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
    marginTop: 6,
  },
  statTrendText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#1E8E3E',
  },
  secondaryStatCard: {
    flex: 1.4,
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    flexDirection: 'row',
    justifyContent: 'space-between',
    elevation: 2,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
  },
  secondaryStatCol: {
    flex: 1,
  },
  secondaryStatVal: {
    fontSize: 16,
    fontWeight: '800',
    color: '#112D27',
  },
  attentionCard: {
    backgroundColor: '#FFF5F0',
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: '#FED7AA',
    marginBottom: 18,
  },
  attentionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 14,
  },
  attentionTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#FF3B30',
  },
  attentionViewAll: {
    fontSize: 12,
    fontWeight: '700',
    color: '#FF3B30',
  },
  attentionList: {
    gap: 12,
  },
  attentionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  attentionItemLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  attentionBadgeCircle: {
    width: 20,
    height: 20,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  attentionBadgeNum: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: '800',
  },
  attentionText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#112D27',
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#112D27',
  },
  sectionViewAll: {
    fontSize: 12,
    fontWeight: '700',
    color: '#7E9188',
  },
  emptyCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#ECE9E0',
    gap: 4,
  },
  emptyTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#112D27',
    marginTop: 4,
  },
  emptySub: {
    fontSize: 12,
    color: '#7E9188',
    textAlign: 'center',
  },
  liveOrderRow: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    paddingVertical: 14,
    paddingHorizontal: 14,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
    elevation: 1,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
  },
  orderAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#E6F0EC',
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderAvatarLetter: {
    fontSize: 15,
    fontWeight: '800',
    color: '#064E45',
  },
  liveOrderNumber: {
    fontSize: 14,
    fontWeight: '800',
    color: '#112D27',
  },
  liveOrderPrice: {
    fontSize: 12,
    fontWeight: '700',
    color: '#7E9188',
    marginTop: 3,
  },
  orderMetaCol: {
    marginRight: 10,
    alignItems: 'flex-end',
  },
  metaItemsCount: {
    fontSize: 11,
    fontWeight: '600',
    color: '#7E9188',
  },
  metaTime: {
    fontSize: 10,
    color: '#7E9188',
    marginTop: 2,
  },
  pillBadge: {
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 8,
  },
  pillBadgeText: {
    fontSize: 10,
    fontWeight: '700',
  },
  acceptPillBtn: {
    backgroundColor: '#064E45',
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 14,
  },
  acceptPillBtnText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '700',
  },
  trackPillBtn: {
    backgroundColor: '#E6F0EC',
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 14,
  },
  trackPillBtnText: {
    color: '#064E45',
    fontSize: 12,
    fontWeight: '700',
  },
  viewPillBtn: {
    backgroundColor: '#F1EFE9',
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 14,
  },
  viewPillBtnText: {
    color: '#112D27',
    fontSize: 12,
    fontWeight: '700',
  },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', padding: 20 },
  qrDialog: { borderRadius: 24, padding: 22, alignItems: 'center' },
  qrHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', width: '100%', marginBottom: 10 },
  qrTitle: { fontSize: 18, fontWeight: '800' },
  qrInstructions: { fontSize: 12, textAlign: 'center', marginBottom: 14 },
  qrImageWrapper: { width: 220, height: 220, backgroundColor: '#FFFFFF', borderRadius: 16, padding: 10, alignItems: 'center', justifyContent: 'center', marginBottom: 14 },
  qrImage: { width: 200, height: 200 },
  pairingCodeBox: { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 12, alignItems: 'center', marginBottom: 14 },
  pairingCodeText: { fontSize: 18, fontWeight: '800', letterSpacing: 2, marginTop: 2 },
  closeModalBtn: { width: '100%', paddingVertical: 12, borderRadius: 14, alignItems: 'center' },
  closeModalBtnText: { color: '#FFFFFF', fontSize: 14, fontWeight: '700' },
});
