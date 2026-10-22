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
      {/* ── Top Dual-Glass Executive Header ── */}
      <View style={[styles.topBar, { backgroundColor: theme.background }]}>
        <View style={styles.brandRow}>
          <Image
            source={require('../../assets/brand-symbol.png')}
            style={styles.brandIcon}
            resizeMode="contain"
          />
          <View>
            <Text style={[styles.brandLogoText, { color: theme.text }]}>foodio<Text style={{ color: theme.primary }}>.</Text></Text>
            <View style={styles.beaconRow}>
              <View style={[styles.beaconDot, { backgroundColor: isOpen ? '#10B981' : '#EF4444' }]} />
              <Text style={styles.beaconText}>
                {isOpen ? 'Kitchen Live • WhatsApp Sync' : 'Store Offline'}
              </Text>
            </View>
          </View>
        </View>

        <View style={styles.topActions}>
          <TouchableOpacity
            onPress={() => toggleMutation.mutate()}
            activeOpacity={0.8}
            style={[
              styles.statusTogglePill,
              {
                backgroundColor: isOpen ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)',
                borderColor: isOpen ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)',
              },
            ]}
          >
            <Ionicons
              name={isOpen ? 'power' : 'power-outline'}
              size={14}
              color={isOpen ? '#10B981' : '#EF4444'}
            />
            <Text style={[styles.statusToggleText, { color: isOpen ? '#10B981' : '#EF4444' }]}>
              {isOpen ? 'OPEN' : 'CLOSED'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/more/reports')}
            style={[styles.headerIconBtn, { backgroundColor: theme.glassCard, borderColor: theme.glassBorder }]}
          >
            <Ionicons name="notifications-outline" size={19} color={theme.text} />
            {totalIssues > 0 && <View style={styles.notificationDot} />}
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/(app)/more')}
            style={[styles.avatarPill, { borderColor: theme.primary }]}
          >
            <View style={styles.avatarInner}>
              <Text style={[styles.avatarInitials, { color: theme.primary }]}>
                {(data?.restaurant?.name || profileData?.restaurant?.name || 'R')[0].toUpperCase()}
              </Text>
            </View>
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isManualRefreshing} onRefresh={handleManualRefresh} tintColor={theme.primary} />}
      >
        {/* ── Quick Quick-Launch Glass Dock ── */}
        <View style={styles.quickLaunchDock}>
          <TouchableOpacity
            style={[styles.quickLaunchPill, { backgroundColor: theme.glassCard, borderColor: theme.glassBorder }]}
            onPress={() => router.push('/(app)/pos')}
          >
            <Ionicons name="calculator-outline" size={16} color={theme.primary} />
            <Text style={[styles.quickLaunchText, { color: theme.text }]}>POS Register</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.quickLaunchPill, { backgroundColor: theme.glassCard, borderColor: theme.glassBorder }]}
            onPress={() => router.push('/(app)/kitchen')}
          >
            <Ionicons name="restaurant-outline" size={16} color="#E06B43" />
            <Text style={[styles.quickLaunchText, { color: theme.text }]}>Kitchen KDS</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.quickLaunchPill, { backgroundColor: theme.glassCard, borderColor: theme.glassBorder }]}
            onPress={() => router.push('/(auth)/dine-in')}
          >
            <Ionicons name="qr-code-outline" size={16} color="#F59E0B" />
            <Text style={[styles.quickLaunchText, { color: theme.text }]}>Customer QR</Text>
          </TouchableOpacity>
        </View>

        {/* ── Greeting & Restaurant Header ── */}
        <View style={styles.greetingSection}>
          <Text style={[styles.greetingLabel, { color: theme.textMuted }]}>COMMAND CENTER</Text>
          <Text style={[styles.ownerName, { color: theme.text }]}>
            {data?.restaurant?.name || profileData?.restaurant?.name || 'AuraBistro Flagship'}
          </Text>
        </View>

        {/* ── Metric Summary Cards (Glassmorphic Alabaster Duo) ── */}
        <View style={styles.statsTwoCardsRow}>
          {/* Card 1: Today's Sales with Growth Badge */}
          <View style={[styles.salesStatCard, { backgroundColor: theme.glassCard, borderColor: theme.glassBorder }]}>
            <View style={styles.statIconBadge}>
              <Ionicons name="wallet-outline" size={16} color={theme.primary} />
            </View>
            <Text style={[styles.statCardLabel, { color: theme.textMuted }]}>TODAY'S REVENUE</Text>
            <Text style={[styles.salesStatAmount, { color: theme.text }]}>
              Rs {Number(kpis.today_sales || 0).toLocaleString()}
            </Text>
            <View style={styles.statTrendRow}>
              <View style={styles.livePulseDot} />
              <Text style={styles.statTrendText}>Live Sync Active</Text>
            </View>
          </View>

          {/* Card 2: Total Orders & Avg. Order Split */}
          <View style={[styles.secondaryStatCard, { backgroundColor: theme.glassCard, borderColor: theme.glassBorder }]}>
            <View style={styles.secondaryStatCol}>
              <View style={styles.statIconBadge}>
                <Ionicons name="receipt-outline" size={16} color="#E06B43" />
              </View>
              <Text style={[styles.statCardLabel, { color: theme.textMuted }]}>ORDERS</Text>
              <Text style={[styles.secondaryStatVal, { color: theme.text }]}>{kpis.today_orders ?? 0}</Text>
              <Text style={[styles.secondaryStatSub, { color: theme.textMuted }]}>Tickets today</Text>
            </View>

            <View style={[styles.cardDivider, { backgroundColor: theme.glassBorder }]} />

            <View style={styles.secondaryStatCol}>
              <View style={styles.statIconBadge}>
                <Ionicons name="analytics-outline" size={16} color="#F59E0B" />
              </View>
              <Text style={[styles.statCardLabel, { color: theme.textMuted }]}>AVG CHECK</Text>
              <Text style={[styles.secondaryStatVal, { color: theme.text }]}>
                Rs {Number(kpis.aov || 0).toLocaleString()}
              </Text>
              <Text style={[styles.secondaryStatSub, { color: theme.textMuted }]}>Per customer</Text>
            </View>
          </View>
        </View>

        {/* ── Needs Attention Alert Card (Live Data Only) ── */}
        <View style={[styles.attentionCard, { backgroundColor: totalIssues > 0 ? 'rgba(239, 68, 68, 0.05)' : theme.glassCard, borderColor: totalIssues > 0 ? 'rgba(239, 68, 68, 0.2)' : theme.glassBorder }]}>
          <View style={styles.attentionHeader}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
              <Ionicons
                name={totalIssues > 0 ? 'alert-circle' : 'checkmark-circle'}
                size={20}
                color={totalIssues > 0 ? '#EF4444' : '#10B981'}
              />
              <Text style={[styles.attentionTitle, { color: totalIssues > 0 ? '#DC2626' : '#10B981' }]}>
                {totalIssues > 0 ? 'Needs Attention' : 'All Clear • Zero Bottlenecks'}
              </Text>
            </View>
            <TouchableOpacity onPress={() => router.push('/(app)/orders')}>
              <Text style={[styles.attentionViewAll, { color: theme.primary }]}>View Orders &gt;</Text>
            </TouchableOpacity>
          </View>

          {totalIssues === 0 ? (
            <View style={{ paddingVertical: 6, flexDirection: 'row', alignItems: 'center', gap: 8 }}>
              <Ionicons name="shield-checkmark" size={18} color="#10B981" />
              <Text style={{ fontSize: 13, color: theme.textMuted, fontWeight: '500' }}>
                All systems operating at peak hospitality standards.
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
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#EF4444' }]}>
                      <Text style={styles.attentionBadgeNum}>{pendingCount}</Text>
                    </View>
                    <Text style={[styles.attentionText, { color: theme.text }]}>
                      {pendingCount} Incoming Order{pendingCount > 1 ? 's' : ''} (Awaiting KOT)
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color={theme.textMuted} />
                </TouchableOpacity>
              )}

              {/* Item 2: Preparing */}
              {inKitchenCount > 0 && (
                <TouchableOpacity
                  onPress={() => router.push('/(app)/orders')}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#F59E0B' }]}>
                      <Text style={styles.attentionBadgeNum}>{inKitchenCount}</Text>
                    </View>
                    <Text style={[styles.attentionText, { color: theme.text }]}>
                      {inKitchenCount} In Kitchen / Preparing
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color={theme.textMuted} />
                </TouchableOpacity>
              )}

              {/* Item 3: Delivery Issue */}
              {deliveryIssuesCount > 0 && (
                <TouchableOpacity
                  onPress={() => router.push('/(app)/delivery')}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#3B82F6' }]}>
                      <Text style={styles.attentionBadgeNum}>{deliveryIssuesCount}</Text>
                    </View>
                    <Text style={[styles.attentionText, { color: theme.text }]}>
                      {deliveryIssuesCount} Unassigned Delivery{deliveryIssuesCount > 1 ? 's' : ''}
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color={theme.textMuted} />
                </TouchableOpacity>
              )}

              {/* Item 4: WhatsApp Bot Disconnected */}
              {isBotDisconnected && (
                <TouchableOpacity
                  onPress={handleFetchQr}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#EF4444' }]}>
                      <Ionicons name="logo-whatsapp" size={11} color="#FFFFFF" />
                    </View>
                    <Text style={[styles.attentionText, { color: theme.text }]}>WhatsApp Bot Disconnected</Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color={theme.textMuted} />
                </TouchableOpacity>
              )}

              {/* Item 5: Unavailable Items */}
              {unavailableCount > 0 && (
                <TouchableOpacity
                  onPress={() => router.push('/(app)/menu')}
                  style={styles.attentionItem}
                >
                  <View style={styles.attentionItemLeft}>
                    <View style={[styles.attentionBadgeCircle, { backgroundColor: '#6B7280' }]}>
                      <Text style={styles.attentionBadgeNum}>{unavailableCount}</Text>
                    </View>
                    <Text style={[styles.attentionText, { color: theme.text }]}>
                      {unavailableCount} 86'd / Sold Out Item{unavailableCount > 1 ? 's' : ''}
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color={theme.textMuted} />
                </TouchableOpacity>
              )}
            </View>
          )}
        </View>

        {/* ── Live Orders Section Header ── */}
        <View style={styles.sectionHeader}>
          <View>
            <Text style={[styles.sectionTitle, { color: theme.text }]}>Live Orders Pipeline</Text>
            <Text style={[styles.sectionSubtitle, { color: theme.textMuted }]}>Real-time dine-in & delivery flow</Text>
          </View>
          <TouchableOpacity onPress={() => router.push('/(app)/orders')}>
            <Text style={[styles.sectionViewAll, { color: theme.primary }]}>View All &gt;</Text>
          </TouchableOpacity>
        </View>

        {/* ── Live Orders List ── */}
        {recentOrders.length === 0 ? (
          <View style={[styles.emptyCard, { backgroundColor: theme.glassCard, borderColor: theme.glassBorder }]}>
            <Ionicons name="receipt-outline" size={38} color={theme.textMuted} />
            <Text style={[styles.emptyTitle, { color: theme.text }]}>No live orders right now</Text>
            <Text style={[styles.emptySub, { color: theme.textMuted }]}>
              Orders placed via WhatsApp, Table QR, or POS will appear here instantly.
            </Text>
          </View>
        ) : (
          recentOrders.slice(0, 5).map((ord: any) => {
            const isNew = ord.status === 'pending' || ord.status === 'confirmed';
            const isPrep = ord.status === 'preparing';
            const isDelivery = ord.status === 'out_for_delivery';

            return (
              <View
                key={ord.id}
                style={[
                  styles.liveOrderRow,
                  { backgroundColor: theme.glassCard, borderColor: theme.glassBorder },
                ]}
              >
                {/* Customer Avatar Thumbnail */}
                <View style={[styles.orderAvatar, { backgroundColor: theme.glassBorder }]}>
                  <Text style={[styles.orderAvatarLetter, { color: theme.primary }]}>
                    {(ord.customer_name || 'C')[0].toUpperCase()}
                  </Text>
                </View>

                {/* Middle details: ID & Price on left, Status badge */}
                <View style={{ flex: 1, marginLeft: 12 }}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                    <Text style={[styles.liveOrderNumber, { color: theme.text }]}>
                      {ord.order_number ? `#${ord.order_number.replace('#', '')}` : `#${ord.id}`}
                    </Text>
                    {isNew && (
                      <View style={[styles.pillBadge, { backgroundColor: 'rgba(239, 68, 68, 0.12)' }]}>
                        <Text style={[styles.pillBadgeText, { color: '#EF4444' }]}>New</Text>
                      </View>
                    )}
                    {isPrep && (
                      <View style={[styles.pillBadge, { backgroundColor: 'rgba(245, 158, 11, 0.14)' }]}>
                        <Text style={[styles.pillBadgeText, { color: '#D97706' }]}>Preparing</Text>
                      </View>
                    )}
                    {isDelivery && (
                      <View style={[styles.pillBadge, { backgroundColor: 'rgba(59, 130, 246, 0.12)' }]}>
                        <Text style={[styles.pillBadgeText, { color: '#2563EB' }]}>Out for delivery</Text>
                      </View>
                    )}
                  </View>

                  <Text style={[styles.liveOrderPrice, { color: theme.primary }]}>
                    Rs {Number(ord.total).toLocaleString()}
                  </Text>
                </View>

                {/* Time & Counter details (Live from database) */}
                <View style={styles.orderMetaCol}>
                  <Text style={[styles.metaItemsCount, { color: theme.textMuted }]}>
                    {ord.items_count
                      ? `${ord.items_count} item${ord.items_count > 1 ? 's' : ''}`
                      : ord.items_summary
                      ? `${ord.items_summary.split(',').length} item${ord.items_summary.split(',').length > 1 ? 's' : ''}`
                      : '1 item'}
                  </Text>
                  <Text style={[styles.metaTime, { color: theme.textMuted }]}>{ord.time || ord.created_at || ''}</Text>
                </View>

                {/* Right Action Button */}
                {isNew ? (
                  <TouchableOpacity
                    onPress={() => router.push('/(app)/orders')}
                    style={[styles.acceptPillBtn, { backgroundColor: theme.primary }]}
                  >
                    <Text style={styles.acceptPillBtnText}>Accept</Text>
                  </TouchableOpacity>
                ) : isDelivery ? (
                  <TouchableOpacity
                    onPress={() => router.push('/(app)/delivery')}
                    style={[styles.trackPillBtn, { backgroundColor: 'rgba(16, 185, 129, 0.14)' }]}
                  >
                    <Text style={[styles.trackPillBtnText, { color: theme.primary }]}>Track</Text>
                  </TouchableOpacity>
                ) : (
                  <TouchableOpacity
                    onPress={() => router.push('/(app)/orders')}
                    style={[styles.viewPillBtn, { backgroundColor: theme.glassBorder }]}
                  >
                    <Text style={[styles.viewPillBtnText, { color: theme.text }]}>View</Text>
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
    paddingTop: 10,
    paddingBottom: 12,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  brandIcon: {
    width: 28,
    height: 28,
  },
  brandLogoText: {
    fontSize: 20,
    fontWeight: '900',
    letterSpacing: -0.4,
  },
  beaconRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
  },
  beaconDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  beaconText: {
    fontSize: 10,
    color: '#78716C',
    fontWeight: '600',
    letterSpacing: 0.2,
  },
  topActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statusTogglePill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 16,
    borderWidth: 1,
  },
  statusToggleText: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  headerIconBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    position: 'relative',
  },
  notificationDot: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#EF4444',
  },
  avatarPill: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: 'rgba(224, 107, 67, 0.1)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
  },
  avatarInner: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarInitials: {
    fontSize: 14,
    fontWeight: '800',
  },
  scrollContent: {
    paddingHorizontal: 18,
    paddingBottom: 110,
  },
  quickLaunchDock: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 6,
    marginBottom: 16,
  },
  quickLaunchPill: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: 10,
    borderRadius: 16,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 1,
  },
  quickLaunchText: {
    fontSize: 12,
    fontWeight: '700',
  },
  greetingSection: {
    marginBottom: 14,
  },
  greetingLabel: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1,
  },
  ownerName: {
    fontSize: 22,
    fontWeight: '900',
    letterSpacing: -0.4,
    marginTop: 2,
  },
  statsTwoCardsRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 16,
  },
  salesStatCard: {
    flex: 1.15,
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  statIconBadge: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: 'rgba(255, 255, 255, 0.5)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  statCardLabel: {
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.6,
    marginBottom: 4,
  },
  salesStatAmount: {
    fontSize: 20,
    fontWeight: '900',
    letterSpacing: -0.3,
  },
  statTrendRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 8,
  },
  livePulseDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#10B981',
  },
  statTrendText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#10B981',
  },
  secondaryStatCard: {
    flex: 1.35,
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  secondaryStatCol: {
    flex: 1,
  },
  cardDivider: {
    width: 1,
    height: '80%',
    marginHorizontal: 8,
  },
  secondaryStatVal: {
    fontSize: 16,
    fontWeight: '900',
    letterSpacing: -0.2,
  },
  secondaryStatSub: {
    fontSize: 10,
    marginTop: 2,
  },
  attentionCard: {
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    marginBottom: 20,
  },
  attentionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  attentionTitle: {
    fontSize: 13,
    fontWeight: '800',
    letterSpacing: 0.2,
  },
  attentionViewAll: {
    fontSize: 12,
    fontWeight: '700',
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
    fontWeight: '600',
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-end',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: '900',
    letterSpacing: -0.3,
  },
  sectionSubtitle: {
    fontSize: 11,
    marginTop: 1,
  },
  sectionViewAll: {
    fontSize: 12,
    fontWeight: '700',
  },
  emptyCard: {
    borderRadius: 22,
    padding: 28,
    alignItems: 'center',
    borderWidth: 1,
    gap: 6,
  },
  emptyTitle: {
    fontSize: 15,
    fontWeight: '800',
    marginTop: 6,
  },
  emptySub: {
    fontSize: 12,
    textAlign: 'center',
    lineHeight: 18,
  },
  liveOrderRow: {
    borderRadius: 20,
    paddingVertical: 14,
    paddingHorizontal: 14,
    borderWidth: 1,
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1,
  },
  orderAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderAvatarLetter: {
    fontSize: 15,
    fontWeight: '900',
  },
  liveOrderNumber: {
    fontSize: 14,
    fontWeight: '800',
  },
  liveOrderPrice: {
    fontSize: 13,
    fontWeight: '800',
    marginTop: 2,
  },
  orderMetaCol: {
    marginRight: 10,
    alignItems: 'flex-end',
  },
  metaItemsCount: {
    fontSize: 11,
    fontWeight: '600',
  },
  metaTime: {
    fontSize: 10,
    marginTop: 2,
  },
  pillBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 10,
  },
  pillBadgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
  acceptPillBtn: {
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 14,
  },
  acceptPillBtnText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
  trackPillBtn: {
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 14,
  },
  trackPillBtnText: {
    fontSize: 12,
    fontWeight: '800',
  },
  viewPillBtn: {
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 14,
  },
  viewPillBtnText: {
    fontSize: 12,
    fontWeight: '800',
  },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'center', padding: 20 },
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
