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

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['command-center'],
    queryFn: () => apiClient<any>('/dashboard/command-center'),
    refetchInterval: 12000,
  });

  const { data: profileData } = useQuery({
    queryKey: ['owner-profile'],
    queryFn: () => apiClient<any>('/profile'),
    refetchInterval: 15000,
  });

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
  const botStatus = profileData?.profile?.bot_status || 'connected';
  const isBotConnected = botStatus === 'connected' || botStatus === 'open';

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Top App Bar (Feature 8: Emergency Store Control & Busy Mode) ── */}
      <View style={[styles.topBar, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
        <View style={styles.storeRow}>
          <View style={[styles.avatarBox, { backgroundColor: theme.primaryLight }]}>
            <Ionicons name="restaurant" size={20} color={theme.primary} />
          </View>
          <View style={{ flex: 1, marginLeft: 12 }}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
              <Text style={[styles.restaurantName, { color: theme.text }]} numberOfLines={1}>
                {data?.restaurant?.name || 'Grill Cafe'}
              </Text>
              <View style={[styles.statusDot, { backgroundColor: isOpen ? '#22C55E' : '#EF4444' }]} />
            </View>
            <Text style={[styles.restaurantBranch, { color: theme.textMuted }]}>
              {isOpen ? 'Store Open • Accepting Orders' : 'Store CLOSED / Busy Mode'}
            </Text>
          </View>
        </View>

        <View style={styles.topActions}>
          <TouchableOpacity
            onPress={() => router.push('/more/reports')}
            style={[styles.iconButton, { backgroundColor: theme.surfaceSubtle }]}
          >
            <Ionicons name="notifications-outline" size={20} color={theme.text} />
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
      >
        {/* ── Persistent Emergency Store Toggle Strip (Feature 8) ── */}
        <View
          style={[
            styles.toggleStrip,
            { backgroundColor: isOpen ? theme.surface : '#FEF2F2', borderColor: isOpen ? theme.border : '#FCA5A5' },
          ]}
        >
          <View style={{ flex: 1, marginRight: 10 }}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
              <Ionicons
                name={isOpen ? 'flash' : 'pause-circle'}
                size={18}
                color={isOpen ? '#16A34A' : '#DC2626'}
              />
              <Text style={[styles.toggleTitle, { color: isOpen ? theme.text : '#DC2626' }]}>
                {isOpen ? 'Kitchen Open (Accepting Orders)' : 'Busy Mode / Store Paused'}
              </Text>
            </View>
            <Text style={[styles.toggleSubtitle, { color: theme.textMuted }]}>
              {isOpen ? 'WhatsApp Bot & QR orders active' : 'Incoming WhatsApp orders paused'}
            </Text>
          </View>
          <Switch
            value={isOpen}
            onValueChange={() => toggleMutation.mutate()}
            trackColor={{ false: '#EF4444', true: '#22C55E' }}
            thumbColor="#FFFFFF"
          />
        </View>

        {/* ── WhatsApp AI Bot Health Card (Feature 4) ── */}
        <View style={[styles.botHealthCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <View style={styles.botCardHeader}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
              <View style={[styles.botIconWrap, { backgroundColor: isBotConnected ? '#DCFCE7' : '#FEE2E2' }]}>
                <Ionicons
                  name="logo-whatsapp"
                  size={20}
                  color={isBotConnected ? '#16A34A' : '#DC2626'}
                />
              </View>
              <View>
                <Text style={[styles.botCardTitle, { color: theme.text }]}>WhatsApp AI Bot Health</Text>
                <Text style={[styles.botCardSub, { color: theme.textMuted }]}>
                  {profileData?.profile?.whatsapp_number || 'Ordering Bot'}
                </Text>
              </View>
            </View>

            <View style={[styles.botStatusBadge, { backgroundColor: isBotConnected ? '#DCFCE7' : '#FEE2E2' }]}>
              <View style={[styles.botStatusDot, { backgroundColor: isBotConnected ? '#16A34A' : '#DC2626' }]} />
              <Text style={[styles.botStatusText, { color: isBotConnected ? '#16A34A' : '#DC2626' }]}>
                {isBotConnected ? 'Connected 🟢' : 'Disconnected 🔴'}
              </Text>
            </View>
          </View>

          {/* Bot Action Buttons */}
          <View style={styles.botActionsRow}>
            <TouchableOpacity
              onPress={() => router.push('/more/chat-oversight')}
              style={[styles.botActionPill, { backgroundColor: theme.primaryLight }]}
            >
              <Ionicons name="chatbubbles" size={15} color={theme.primary} />
              <Text style={[styles.botActionPillText, { color: theme.primary }]}>Live Chats & Takeover</Text>
            </TouchableOpacity>

            {!isBotConnected ? (
              <TouchableOpacity
                onPress={handleFetchQr}
                style={[styles.botActionPill, { backgroundColor: '#FEF3C7' }]}
              >
                <Ionicons name="qr-code" size={15} color="#D97706" />
                <Text style={[styles.botActionPillText, { color: '#D97706' }]}>Scan Bot QR</Text>
              </TouchableOpacity>
            ) : (
              <TouchableOpacity
                onPress={() => restartBotMutation.mutate()}
                style={[styles.botActionPill, { backgroundColor: theme.surfaceSubtle }]}
              >
                <Ionicons name="refresh" size={15} color={theme.textMuted} />
                <Text style={[styles.botActionPillText, { color: theme.textMuted }]}>Restart Bot</Text>
              </TouchableOpacity>
            )}
          </View>
        </View>

        {/* ── Hero Sales Card ── */}
        <View style={[styles.salesHeroCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <View style={styles.salesHeroTop}>
            <View>
              <Text style={[styles.salesHeroLabel, { color: theme.textMuted }]}>Today's Total Sales</Text>
              <Text style={[styles.salesHeroAmount, { color: theme.text }]}>
                Rs. {Number(kpis.today_sales || 0).toLocaleString()}
              </Text>
            </View>
            <View style={styles.growthBadge}>
              <Ionicons name="trending-up" size={14} color="#22C55E" />
              <Text style={styles.growthText}>+12.5%</Text>
            </View>
          </View>

          {/* Sparkline Visual Simulation Bar */}
          <View style={styles.sparklineContainer}>
            <View style={[styles.sparklineBar, { height: '35%', backgroundColor: '#E2E8F0' }]} />
            <View style={[styles.sparklineBar, { height: '48%', backgroundColor: '#E2E8F0' }]} />
            <View style={[styles.sparklineBar, { height: '62%', backgroundColor: '#CBD5E1' }]} />
            <View style={[styles.sparklineBar, { height: '45%', backgroundColor: '#CBD5E1' }]} />
            <View style={[styles.sparklineBar, { height: '80%', backgroundColor: theme.primaryLight }]} />
            <View style={[styles.sparklineBar, { height: '95%', backgroundColor: theme.primary }]} />
            <View style={[styles.sparklineBar, { height: '70%', backgroundColor: theme.primary }]} />
          </View>
        </View>

        {/* ── 4 Crisp KPI Tiles ── */}
        <View style={styles.kpiGrid}>
          {/* Tile 1: Total Orders */}
          <View style={[styles.kpiTile, { backgroundColor: theme.surface, borderColor: theme.border }]}>
            <View style={[styles.kpiIconWrap, { backgroundColor: '#E8F5F2' }]}>
              <Ionicons name="receipt" size={20} color="#064E45" />
            </View>
            <Text style={[styles.kpiVal, { color: theme.text }]}>{kpis.today_orders || 0}</Text>
            <Text style={[styles.kpiName, { color: theme.textMuted }]}>Total Orders</Text>
          </View>

          {/* Tile 2: Avg Order Value */}
          <View style={[styles.kpiTile, { backgroundColor: theme.surface, borderColor: theme.border }]}>
            <View style={[styles.kpiIconWrap, { backgroundColor: '#FFF0DE' }]}>
              <Ionicons name="wallet" size={20} color="#FF941F" />
            </View>
            <Text style={[styles.kpiVal, { color: theme.text }]}>
              Rs. {Number(kpis.aov || 0).toLocaleString()}
            </Text>
            <Text style={[styles.kpiName, { color: theme.textMuted }]}>Avg. Ticket</Text>
          </View>

          {/* Tile 3: Completed Orders */}
          <View style={[styles.kpiTile, { backgroundColor: theme.surface, borderColor: theme.border }]}>
            <View style={[styles.kpiIconWrap, { backgroundColor: '#F0FDF4' }]}>
              <Ionicons name="checkmark-done" size={20} color="#22C55E" />
            </View>
            <Text style={[styles.kpiVal, { color: '#22C55E' }]}>{kpis.completed || 0}</Text>
            <Text style={[styles.kpiName, { color: theme.textMuted }]}>Completed</Text>
          </View>

          {/* Tile 4: In Progress */}
          <View style={[styles.kpiTile, { backgroundColor: theme.surface, borderColor: theme.border }]}>
            <View style={[styles.kpiIconWrap, { backgroundColor: '#FAF5FF' }]}>
              <Ionicons name="hourglass" size={20} color="#A855F7" />
            </View>
            <Text style={[styles.kpiVal, { color: '#A855F7' }]}>{attention.pending_orders || 0}</Text>
            <Text style={[styles.kpiName, { color: theme.textMuted }]}>In Kitchen</Text>
          </View>
        </View>

        {/* ── Action Quick Strip ── */}
        <View style={styles.actionStrip}>
          <TouchableOpacity
            activeOpacity={0.85}
            onPress={() => router.push('/(app)/pos')}
            style={[styles.primaryActionBtn, { backgroundColor: theme.primary }]}
          >
            <Ionicons name="add-circle" size={20} color="#FFFFFF" />
            <Text style={styles.primaryActionText}>Create POS Order</Text>
          </TouchableOpacity>

          <TouchableOpacity
            activeOpacity={0.85}
            onPress={() => router.push('/(app)/orders')}
            style={[styles.secondaryActionBtn, { backgroundColor: theme.surface, borderColor: theme.border }]}
          >
            <Ionicons name="receipt" size={20} color={theme.text} />
            <Text style={[styles.secondaryActionText, { color: theme.text }]}>Live Queue ({attention.pending_orders || 0})</Text>
          </TouchableOpacity>
        </View>

        {/* ── Live Orders Section Header ── */}
        <View style={styles.sectionHeader}>
          <Text style={[styles.sectionTitle, { color: theme.text }]}>Live Orders Pipeline</Text>
          <TouchableOpacity onPress={() => router.push('/(app)/orders')}>
            <Text style={[styles.seeAllText, { color: theme.primary }]}>View All →</Text>
          </TouchableOpacity>
        </View>

        {/* Order Cards Preview */}
        {recentOrders.length === 0 ? (
          <View style={[styles.emptyCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
            <Ionicons name="receipt-outline" size={36} color={theme.textMuted} />
            <Text style={[styles.emptyTitle, { color: theme.text }]}>No orders yet today</Text>
            <Text style={[styles.emptySub, { color: theme.textMuted }]}>
              Orders placed via POS or WhatsApp will appear here in real-time.
            </Text>
          </View>
        ) : (
          recentOrders.slice(0, 5).map((ord: any) => (
            <TouchableOpacity
              key={ord.id}
              activeOpacity={0.8}
              onPress={() => router.push('/(app)/orders')}
              style={[styles.orderItemCard, { backgroundColor: theme.surface, borderColor: theme.border }]}
            >
              <View style={styles.orderTop}>
                <View>
                  <Text style={[styles.orderNumber, { color: theme.text }]}>{ord.order_number}</Text>
                  <Text style={[styles.orderCustomer, { color: theme.textMuted }]}>
                    {ord.customer_name} • {ord.customer_phone}
                  </Text>
                </View>
                <StatusBadge status={ord.status} />
              </View>

              <Text style={[styles.orderSummary, { color: theme.text }]} numberOfLines={1}>
                {ord.items_summary}
              </Text>

              <View style={[styles.orderBottom, { borderTopColor: theme.border }]}>
                <Text style={[styles.orderTime, { color: theme.textMuted }]}>{ord.created_at}</Text>
                <Text style={[styles.orderPrice, { color: theme.primary }]}>
                  Rs. {Number(ord.total).toLocaleString()}
                </Text>
              </View>
            </TouchableOpacity>
          ))
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
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  storeRow: { flexDirection: 'row', alignItems: 'center', flex: 1 },
  avatarBox: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  restaurantName: { fontSize: 17, fontWeight: '800' },
  statusDot: { width: 8, height: 8, borderRadius: 4 },
  restaurantBranch: { fontSize: 12, marginTop: 1 },
  topActions: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  iconButton: { width: 38, height: 38, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  scrollContent: { padding: 16, paddingBottom: 90 },
  toggleStrip: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 12,
  },
  toggleTitle: { fontSize: 14, fontWeight: '700' },
  toggleSubtitle: { fontSize: 12, marginTop: 2 },
  botHealthCard: {
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 14,
    elevation: 1,
  },
  botCardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  botIconWrap: { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  botCardTitle: { fontSize: 13, fontWeight: '700' },
  botCardSub: { fontSize: 11, marginTop: 1 },
  botStatusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 12, gap: 5 },
  botStatusDot: { width: 7, height: 7, borderRadius: 4 },
  botStatusText: { fontSize: 11, fontWeight: '700' },
  botActionsRow: { flexDirection: 'row', gap: 8, marginTop: 12 },
  botActionPill: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 10, gap: 5 },
  botActionPillText: { fontSize: 12, fontWeight: '700' },
  salesHeroCard: {
    padding: 18,
    borderRadius: 20,
    borderWidth: 1,
    marginBottom: 16,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
  },
  salesHeroTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  salesHeroLabel: { fontSize: 13, fontWeight: '600' },
  salesHeroAmount: { fontSize: 26, fontWeight: '800', marginTop: 4, letterSpacing: -0.5 },
  growthBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#DCFCE7',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
    gap: 4,
  },
  growthText: { color: '#16A34A', fontSize: 12, fontWeight: '700' },
  sparklineContainer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    height: 48,
    marginTop: 16,
    paddingTop: 8,
  },
  sparklineBar: { width: '11%', borderRadius: 4 },
  kpiGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', gap: 10, marginBottom: 16 },
  kpiTile: {
    width: '48%',
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    elevation: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.02,
    shadowRadius: 4,
  },
  kpiIconWrap: { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center', marginBottom: 8 },
  kpiVal: { fontSize: 18, fontWeight: '800' },
  kpiName: { fontSize: 12, marginTop: 2 },
  actionStrip: { flexDirection: 'row', gap: 10, marginBottom: 20 },
  primaryActionBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    height: 48,
    borderRadius: 14,
    gap: 8,
  },
  primaryActionText: { color: '#FFFFFF', fontSize: 14, fontWeight: '700' },
  secondaryActionBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    height: 48,
    borderRadius: 14,
    borderWidth: 1,
    gap: 8,
  },
  secondaryActionText: { fontSize: 14, fontWeight: '700' },
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  sectionTitle: { fontSize: 17, fontWeight: '800' },
  seeAllText: { fontSize: 13, fontWeight: '700' },
  emptyCard: { padding: 28, borderRadius: 18, borderWidth: 1, alignItems: 'center', gap: 6 },
  emptyTitle: { fontSize: 15, fontWeight: '700', marginTop: 4 },
  emptySub: { fontSize: 12, textAlign: 'center' },
  orderItemCard: { padding: 14, borderRadius: 16, borderWidth: 1, marginBottom: 10 },
  orderTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 6 },
  orderNumber: { fontSize: 15, fontWeight: '800' },
  orderCustomer: { fontSize: 12, marginTop: 2 },
  orderSummary: { fontSize: 13, marginBottom: 8 },
  orderBottom: { flexDirection: 'row', justifyContent: 'space-between', borderTopWidth: 1, paddingTop: 8 },
  orderTime: { fontSize: 12 },
  orderPrice: { fontSize: 15, fontWeight: '800' },
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
