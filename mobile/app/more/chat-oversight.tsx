import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  Linking,
  Alert,
} from 'react-native';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState, EmptyState } from '../../components/FeedbackStates';
import { Ionicons } from '@expo/vector-icons';

export default function ChatOversightScreen() {
  const { theme } = useAppTheme();
  const queryClient = useQueryClient();

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['bot-conversations'],
    queryFn: () => apiClient<any>('/profile/conversations'),
    refetchInterval: 8000,
  });

  const togglePauseMutation = useMutation({
    mutationFn: (convId: number) =>
      apiClient<any>(`/profile/conversations/${convId}/pause`, { method: 'POST' }),
    onSuccess: (res: any) => {
      queryClient.invalidateQueries({ queryKey: ['bot-conversations'] });
      Alert.alert('Status Updated', res.message || 'Bot takeover state updated.');
    },
    onError: (err: any) => {
      Alert.alert('Action Failed', err.message || 'Could not update takeover state.');
    },
  });

  const openWhatsApp = (phone: string) => {
    const clean = phone.replace(/[^0-9]/g, '');
    const url = `https://wa.me/${clean}`;
    Linking.openURL(url).catch(() => {
      Alert.alert('Cannot Open WhatsApp', 'Please ensure WhatsApp is installed on this device.');
    });
  };

  if (isLoading && !isRefetching) {
    return <LoadingState message="Loading WhatsApp Bot Conversations..." />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => refetch()} />;
  }

  const conversations = data?.conversations ?? [];

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      {/* ── Subheader Overview ── */}
      <View style={[styles.summaryBanner, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
        <View style={styles.bannerRow}>
          <View>
            <Text style={[styles.bannerTitle, { color: theme.text }]}>Live Bot Oversight</Text>
            <Text style={[styles.bannerSub, { color: theme.textMuted }]}>
              {conversations.length} active chats • AI Bot servicing customers in real-time
            </Text>
          </View>
          <TouchableOpacity onPress={() => refetch()} style={[styles.refreshPill, { backgroundColor: theme.primaryLight }]}>
            <Ionicons name="sync" size={16} color={theme.primary} />
            <Text style={[styles.refreshText, { color: theme.primary }]}>Live Sync</Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* ── Conversations Stream ── */}
      {conversations.length === 0 ? (
        <EmptyState
          title="No Active Chats"
          description="Customer chats with the WhatsApp AI Bot will appear here in real time."
        />
      ) : (
        <FlatList
          data={conversations}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.listContent}
          refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
          renderItem={({ item }) => {
            const isPaused = item.is_human_paused;
            return (
              <View
                style={[
                  styles.chatCard,
                  { backgroundColor: theme.surface, borderColor: isPaused ? '#F59E0B' : theme.border },
                ]}
              >
                {/* Top Row: Customer info & Status */}
                <View style={styles.cardHeader}>
                  <View style={{ flex: 1 }}>
                    <View style={styles.nameRow}>
                      <Text style={[styles.customerName, { color: theme.text }]}>
                        {item.customer_name || 'Guest Customer'}
                      </Text>
                      {isPaused && (
                        <View style={styles.pausedBadge}>
                          <Text style={styles.pausedBadgeText}>HUMAN TAKEOVER</Text>
                        </View>
                      )}
                    </View>
                    <Text style={[styles.customerPhone, { color: theme.textMuted }]}>
                      {item.customer_phone} • {item.last_message_at}
                    </Text>
                  </View>

                  <View style={[styles.botStateBadge, { backgroundColor: isPaused ? '#FEF3C7' : '#DCFCE7' }]}>
                    <View
                      style={[
                        styles.stateDot,
                        { backgroundColor: isPaused ? '#D97706' : '#16A34A' },
                      ]}
                    />
                    <Text
                      style={[
                        styles.botStateText,
                        { color: isPaused ? '#B45309' : '#15803D' },
                      ]}
                    >
                      {isPaused ? 'Bot Paused' : 'Bot Active'}
                    </Text>
                  </View>
                </View>

                {/* State / Cart Details */}
                <View style={[styles.detailBox, { backgroundColor: theme.surfaceSubtle }]}>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailLabel, { color: theme.textMuted }]}>Bot Stage:</Text>
                    <Text style={[styles.detailVal, { color: theme.text }]}>
                      {item.state ? item.state.toUpperCase() : 'CONVERSING'}
                    </Text>
                  </View>
                  {item.cart_summary ? (
                    <View style={styles.detailRow}>
                      <Text style={[styles.detailLabel, { color: theme.textMuted }]}>Cart:</Text>
                      <Text style={[styles.detailVal, { color: theme.primary, fontWeight: '700' }]} numberOfLines={1}>
                        {item.cart_summary} (Rs. {Number(item.cart_total || 0).toLocaleString()})
                      </Text>
                    </View>
                  ) : null}
                  {item.customer_address ? (
                    <View style={styles.detailRow}>
                      <Text style={[styles.detailLabel, { color: theme.textMuted }]}>Address:</Text>
                      <Text style={[styles.detailVal, { color: theme.textMuted }]} numberOfLines={1}>
                        📍 {item.customer_address}
                      </Text>
                    </View>
                  ) : null}
                </View>

                {/* Bottom Action Controls: Human Takeover & Open WA */}
                <View style={styles.cardActions}>
                  <TouchableOpacity
                    onPress={() => togglePauseMutation.mutate(item.id)}
                    style={[
                      styles.takeoverBtn,
                      isPaused
                        ? { backgroundColor: '#DCFCE7', borderColor: '#86EFAC' }
                        : { backgroundColor: '#FEF3C7', borderColor: '#FDE68A' },
                    ]}
                  >
                    <Ionicons
                      name={isPaused ? 'play' : 'pause'}
                      size={16}
                      color={isPaused ? '#16A34A' : '#D97706'}
                    />
                    <Text
                      style={[
                        styles.takeoverBtnText,
                        { color: isPaused ? '#16A34A' : '#D97706' },
                      ]}
                    >
                      {isPaused ? 'Resume AI Bot' : 'Pause Bot for Customer'}
                    </Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    onPress={() => openWhatsApp(item.customer_phone)}
                    style={[styles.chatBtn, { backgroundColor: '#25D366' }]}
                  >
                    <Ionicons name="logo-whatsapp" size={16} color="#FFFFFF" />
                    <Text style={styles.chatBtnText}>Reply in WA</Text>
                  </TouchableOpacity>
                </View>
              </View>
            );
          }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  summaryBanner: { paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1 },
  bannerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  bannerTitle: { fontSize: 18, fontWeight: '800' },
  bannerSub: { fontSize: 12, marginTop: 2 },
  refreshPill: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 14, gap: 4 },
  refreshText: { fontSize: 12, fontWeight: '700' },
  listContent: { padding: 14, paddingBottom: 60 },
  chatCard: { padding: 14, borderRadius: 16, borderWidth: 1, marginBottom: 12, elevation: 1 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 10 },
  nameRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  customerName: { fontSize: 15, fontWeight: '700' },
  pausedBadge: { backgroundColor: '#FEF3C7', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  pausedBadgeText: { fontSize: 9, fontWeight: '800', color: '#B45309' },
  customerPhone: { fontSize: 12, marginTop: 2 },
  botStateBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 12, gap: 5 },
  stateDot: { width: 7, height: 7, borderRadius: 4 },
  botStateText: { fontSize: 11, fontWeight: '700' },
  detailBox: { padding: 10, borderRadius: 12, gap: 4, marginBottom: 12 },
  detailRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  detailLabel: { fontSize: 11, fontWeight: '600', minWidth: 65 },
  detailVal: { fontSize: 12, flex: 1 },
  cardActions: { flexDirection: 'row', gap: 8 },
  takeoverBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 10, borderRadius: 12, borderWidth: 1, gap: 6 },
  takeoverBtnText: { fontSize: 12, fontWeight: '700' },
  chatBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingHorizontal: 14, paddingVertical: 10, borderRadius: 12, gap: 6 },
  chatBtnText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700' },
});
