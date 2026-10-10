import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TextInput,
  TouchableOpacity,
  RefreshControl,
  Modal,
  Image,
  Alert,
  ActivityIndicator,
  ScrollView,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient, apiUpload } from '../../services/api/client';
import { LoadingState, ErrorState, EmptyState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import { useRouter } from 'expo-router';

export default function CustomersScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [activeSegment, setActiveSegment] = useState<'all' | 'regular' | 'vip'>('all');
  const [selectedCustomer, setSelectedCustomer] = useState<any | null>(null);

  // Broadcast Studio Modal State
  const [isBroadcastOpen, setIsBroadcastOpen] = useState(false);
  const [broadcastMsg, setBroadcastMsg] = useState('🔥 Flash Deal! Order now and get 20% off on all pizzas and burgers today. Valid till midnight!');
  const [broadcastAudience, setBroadcastAudience] = useState<'all' | 'last30'>('all');
  const [promoImage, setPromoImage] = useState<any | null>(null);
  const [isSendingBroadcast, setIsSendingBroadcast] = useState(false);

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['customers-list', search],
    queryFn: () => apiClient<any>(`/customers?search=${encodeURIComponent(search)}`),
  });

  const { data: customerDetails, isLoading: detailsLoading } = useQuery({
    queryKey: ['customer-detail', selectedCustomer?.id],
    queryFn: () => apiClient<any>(`/customers/${selectedCustomer.id}`),
    enabled: !!selectedCustomer?.id,
  });

  // Pick deal image from camera or gallery
  const handlePickPromoImage = async () => {
    try {
      const res = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        quality: 0.8,
      });

      if (!res.canceled && res.assets && res.assets.length > 0) {
        setPromoImage(res.assets[0]);
      }
    } catch (e: any) {
      Alert.alert('Image Error', 'Could not open photo library.');
    }
  };

  // Dispatch campaign
  const handleSendCampaign = async () => {
    if (!broadcastMsg.trim()) {
      Alert.alert('Required', 'Please enter a promotion message.');
      return;
    }

    try {
      setIsSendingBroadcast(true);
      const formData = new FormData();
      formData.append('message', broadcastMsg.trim());
      formData.append('audience', broadcastAudience);

      if (promoImage) {
        formData.append('deal_image', {
          uri: promoImage.uri,
          name: promoImage.fileName || 'deal_promo.jpg',
          type: promoImage.mimeType || 'image/jpeg',
        } as any);
      }

      const res = await apiUpload('/customers/broadcast', formData);
      Alert.alert('Campaign Dispatched', res.message || 'WhatsApp broadcast sent successfully!');
      setIsBroadcastOpen(false);
      setPromoImage(null);
    } catch (err: any) {
      Alert.alert('Broadcast Error', err.message || 'Could not send broadcast.');
    } finally {
      setIsSendingBroadcast(false);
    }
  };

  if (isLoading && !isRefetching) return <LoadingState message="Loading customers..." />;
  if (error) return <ErrorState message={error.message} onRetry={() => refetch()} />;

  const rawCustomers: any[] = data?.customers ?? [];
  const customers = rawCustomers.filter((c) => {
    if (activeSegment === 'vip') return (c.total_orders && c.total_orders >= 5) || (c.tag || '').toLowerCase() === 'vip';
    if (activeSegment === 'regular') return !c.total_orders || c.total_orders < 5;
    return true;
  });

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Screen 6 Customers Header ── */}
      <View style={[styles.headerArea, { backgroundColor: theme.background }]}>
        <View style={styles.topTitleRow}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
            <Ionicons name="arrow-back" size={22} color="#112D27" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Customers</Text>
          <TouchableOpacity
            onPress={() => setIsBroadcastOpen(true)}
            style={styles.broadcastTriggerBtn}
          >
            <Ionicons name="megaphone-outline" size={20} color="#064E45" />
          </TouchableOpacity>
        </View>

        {/* Search Bar Capsule */}
        <View style={styles.searchBox}>
          <Ionicons name="search-outline" size={18} color="#7E9188" />
          <TextInput
            placeholder="Search customers..."
            placeholderTextColor="#7E9188"
            value={search}
            onChangeText={setSearch}
            style={styles.searchInput}
          />
          {search ? (
            <TouchableOpacity onPress={() => setSearch('')}>
              <Ionicons name="close-circle" size={16} color="#7E9188" />
            </TouchableOpacity>
          ) : null}
        </View>

        {/* Category Pills (All, Regular, VIP) */}
        <View style={styles.filterPillsRow}>
          {[
            { key: 'all', label: 'All' },
            { key: 'regular', label: 'Regular' },
            { key: 'vip', label: 'VIP' },
          ].map((f) => {
            const active = activeSegment === f.key;
            return (
              <TouchableOpacity
                key={f.key}
                onPress={() => setActiveSegment(f.key as any)}
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
                  {f.label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>
      </View>

      {customers.length === 0 ? (
        <EmptyState
          title="No customers found"
          description="Customers are automatically saved when they place WhatsApp or POS orders."
        />
      ) : (
        <FlatList
          data={customers}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.listPadding}
          refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
          renderItem={({ item }) => (
            <TouchableOpacity
              activeOpacity={0.8}
              onPress={() => setSelectedCustomer(item)}
              style={[
                styles.customerCard,
                { backgroundColor: theme.surface, borderColor: theme.border },
              ]}
            >
              <View style={styles.cardTop}>
                <View style={[styles.customerAvatar, { backgroundColor: theme.primary }]}>
                  <Text style={styles.avatarLetter}>
                    {(item.name || 'G')[0].toUpperCase()}
                  </Text>
                </View>
                <View style={{ flex: 1, marginLeft: 12 }}>
                  <Text style={[styles.custName, { color: theme.text }]}>{item.name}</Text>
                  <Text style={[styles.custPhone, { color: theme.textMuted }]}>{item.phone}</Text>
                </View>
                <View style={[styles.tagBadge, { backgroundColor: theme.primaryLight }]}>
                  <Text style={[styles.tagText, { color: theme.primary }]}>
                    {item.tag || 'Regular'}
                  </Text>
                </View>
              </View>

              <Text style={[styles.custAddress, { color: theme.textMuted }]} numberOfLines={1}>
                📍 {item.address || 'Standard Delivery'}
              </Text>

              <View style={[styles.cardFooter, { borderTopColor: theme.border }]}>
                <Text style={[styles.metaText, { color: theme.textMuted }]}>
                  Orders: <Text style={{ color: theme.text, fontWeight: '700' }}>{item.total_orders || 1}</Text>
                </Text>
                <Text style={[styles.metaSpent, { color: theme.primary }]}>
                  Spent: Rs. {Number(item.total_spent || 0).toLocaleString()}
                </Text>
              </View>
            </TouchableOpacity>
          )}
        />
      )}

      {/* ── Deal Broadcast Studio Modal (Feature 5) ── */}
      <Modal visible={isBroadcastOpen} animationType="slide" transparent onRequestClose={() => setIsBroadcastOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.broadcastCard, { backgroundColor: theme.surface }]}>
            <View style={styles.modalHeader}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                <Ionicons name="megaphone" size={22} color={theme.primary} />
                <Text style={[styles.modalTitle, { color: theme.text }]}>Deal Broadcast Studio</Text>
              </View>
              <TouchableOpacity onPress={() => setIsBroadcastOpen(false)}>
                <Ionicons name="close-circle" size={26} color={theme.textMuted} />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false} style={{ maxHeight: 460 }}>
              <Text style={[styles.fieldLabel, { color: theme.text }]}>Target Audience</Text>
              <View style={styles.audienceRow}>
                <TouchableOpacity
                  onPress={() => setBroadcastAudience('all')}
                  style={[
                    styles.audiencePill,
                    broadcastAudience === 'all'
                      ? [styles.audiencePillActive, { backgroundColor: theme.primaryLight, borderColor: theme.primary }]
                      : { borderColor: theme.border },
                  ]}
                >
                  <Text style={{ fontWeight: '700', color: broadcastAudience === 'all' ? theme.primary : theme.text }}>
                    All Past Customers
                  </Text>
                </TouchableOpacity>

                <TouchableOpacity
                  onPress={() => setBroadcastAudience('last30')}
                  style={[
                    styles.audiencePill,
                    broadcastAudience === 'last30'
                      ? [styles.audiencePillActive, { backgroundColor: theme.primaryLight, borderColor: theme.primary }]
                      : { borderColor: theme.border },
                  ]}
                >
                  <Text style={{ fontWeight: '700', color: broadcastAudience === 'last30' ? theme.primary : theme.text }}>
                    Active (Last 30 Days)
                  </Text>
                </TouchableOpacity>
              </View>

              <Text style={[styles.fieldLabel, { color: theme.text }]}>Deal Banner Photo (Optional)</Text>
              {promoImage ? (
                <View style={styles.previewWrap}>
                  <Image source={{ uri: promoImage.uri }} style={styles.previewImage} resizeMode="cover" />
                  <TouchableOpacity onPress={() => setPromoImage(null)} style={styles.removeImageBtn}>
                    <Ionicons name="close" size={16} color="#FFFFFF" />
                  </TouchableOpacity>
                </View>
              ) : (
                <TouchableOpacity onPress={handlePickPromoImage} style={[styles.pickImageBtn, { borderColor: theme.border }]}>
                  <Ionicons name="camera-outline" size={24} color={theme.primary} />
                  <Text style={{ color: theme.primary, fontWeight: '700', marginTop: 4, fontSize: 13 }}>
                    + Select Food Photo / Deal Banner
                  </Text>
                </TouchableOpacity>
              )}

              <Text style={[styles.fieldLabel, { color: theme.text }]}>Promotion Message (Supports {`{name}`})</Text>
              <TextInput
                multiline
                numberOfLines={4}
                value={broadcastMsg}
                onChangeText={setBroadcastMsg}
                style={[styles.msgInput, { color: theme.text, borderColor: theme.border }]}
              />

              <View style={[styles.tipBox, { backgroundColor: theme.surfaceSubtle }]}>
                <Ionicons name="information-circle-outline" size={18} color={theme.textMuted} />
                <Text style={{ fontSize: 11, color: theme.textMuted, flex: 1 }}>
                  Broadcasts are delivered over your WhatsApp bot engine with anti-spam pacing.
                </Text>
              </View>
            </ScrollView>

            <View style={{ marginTop: 12 }}>
              <AppButton
                title={isSendingBroadcast ? 'Sending Campaign...' : 'Launch WhatsApp Broadcast 🚀'}
                loading={isSendingBroadcast}
                onPress={handleSendCampaign}
              />
            </View>
          </View>
        </View>
      </Modal>

      {/* Customer Detail Modal */}
      {selectedCustomer && (
        <Modal visible={!!selectedCustomer} animationType="slide" transparent onRequestClose={() => setSelectedCustomer(null)}>
          <View style={styles.modalBackdrop}>
            <View style={[styles.modalCard, { backgroundColor: theme.surface }]}>
              <View style={styles.modalHeader}>
                <View>
                  <Text style={[styles.modalTitle, { color: theme.text }]}>{selectedCustomer.name}</Text>
                  <Text style={[styles.modalSub, { color: theme.textMuted }]}>{selectedCustomer.phone}</Text>
                </View>
                <TouchableOpacity onPress={() => setSelectedCustomer(null)}>
                  <Ionicons name="close-circle" size={28} color={theme.textMuted} />
                </TouchableOpacity>
              </View>

              <Text style={[styles.historyHeading, { color: theme.text }]}>Past Order History</Text>
              {detailsLoading ? (
                <Text style={{ color: theme.textMuted, marginVertical: 20 }}>Loading orders...</Text>
              ) : (
                <FlatList
                  data={customerDetails?.orders ?? []}
                  keyExtractor={(ord) => String(ord.id)}
                  style={{ maxHeight: 360 }}
                  renderItem={({ item: ord }) => (
                    <View style={[styles.historyRow, { borderBottomColor: theme.border }]}>
                      <View style={{ flex: 1 }}>
                        <Text style={[styles.historyId, { color: theme.text }]}>
                          Order #{ord.daily_order_number || ord.id} • {ord.status.toUpperCase()}
                        </Text>
                        <Text style={[styles.historyItems, { color: theme.textMuted }]} numberOfLines={1}>
                          {ord.items?.map((i: any) => `${i.quantity}x ${i.name}`).join(', ')}
                        </Text>
                      </View>
                      <Text style={[styles.historyTotal, { color: theme.primary }]}>
                        Rs. {Number(ord.total).toLocaleString()}
                      </Text>
                    </View>
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
  headerArea: { paddingHorizontal: 20, paddingTop: 6, paddingBottom: 10 },
  topTitleRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 },
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
  headerTitle: { fontSize: 20, fontWeight: '800', color: '#112D27', letterSpacing: -0.4 },
  broadcastTriggerBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#E6F0EC',
  },
  broadcastTriggerText: { color: '#064E45', fontSize: 12, fontWeight: '700' },
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    paddingHorizontal: 14,
    height: 44,
    marginBottom: 12,
    gap: 8,
  },
  searchInput: { flex: 1, fontSize: 13, color: '#112D27' },
  filterPillsRow: { flexDirection: 'row', gap: 8, marginBottom: 4 },
  filterPill: { paddingHorizontal: 16, paddingVertical: 7, borderRadius: 18 },
  filterPillActive: { backgroundColor: '#064E45' },
  filterPillInactive: { backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#ECE9E0' },
  filterPillText: { fontSize: 12 },
  filterPillTextActive: { color: '#FFFFFF', fontWeight: '700' },
  filterPillTextInactive: { color: '#7E9188', fontWeight: '600' },
  listPadding: { paddingHorizontal: 20, paddingBottom: 100 },
  customerCard: {
    padding: 14,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    backgroundColor: '#FFFFFF',
    marginBottom: 10,
    elevation: 1,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
  },
  cardTop: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
  customerAvatar: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#E6F0EC', alignItems: 'center', justifyContent: 'center' },
  avatarLetter: { color: '#064E45', fontSize: 16, fontWeight: '800' },
  custName: { fontSize: 15, fontWeight: '700', color: '#112D27' },
  custPhone: { fontSize: 12, marginTop: 1, color: '#7E9188' },
  tagBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8, backgroundColor: '#E6F0EC' },
  tagText: { fontSize: 11, fontWeight: '700', color: '#064E45' },
  custAddress: { fontSize: 12, marginBottom: 10, color: '#7E9188' },
  cardFooter: { flexDirection: 'row', justifyContent: 'space-between', borderTopWidth: 1, borderTopColor: '#F5F3ED', paddingTop: 8 },
  metaText: { fontSize: 12, color: '#7E9188' },
  metaSpent: { fontSize: 14, fontWeight: '800', color: '#064E45' },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  broadcastCard: { borderTopLeftRadius: 28, borderTopRightRadius: 28, padding: 20, maxHeight: '88%' },
  modalCard: { borderTopLeftRadius: 28, borderTopRightRadius: 28, padding: 20, maxHeight: '80%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 },
  modalTitle: { fontSize: 18, fontWeight: '800' },
  modalSub: { fontSize: 13, marginTop: 2 },
  fieldLabel: { fontSize: 13, fontWeight: '700', marginTop: 10, marginBottom: 6 },
  audienceRow: { flexDirection: 'row', gap: 10, marginBottom: 10 },
  audiencePill: { flex: 1, paddingVertical: 10, borderWidth: 1, borderRadius: 12, alignItems: 'center' },
  audiencePillActive: { borderWidth: 1.5 },
  pickImageBtn: {
    height: 70,
    borderWidth: 1.5,
    borderStyle: 'dashed',
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  previewWrap: { position: 'relative', height: 110, borderRadius: 14, overflow: 'hidden', marginBottom: 8 },
  previewImage: { width: '100%', height: '100%' },
  removeImageBtn: {
    position: 'absolute',
    top: 6,
    right: 6,
    backgroundColor: 'rgba(0,0,0,0.6)',
    width: 24,
    height: 24,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  msgInput: { height: 80, borderWidth: 1, borderRadius: 12, padding: 10, textAlignVertical: 'top', fontSize: 13 },
  tipBox: { flexDirection: 'row', alignItems: 'center', gap: 6, padding: 10, borderRadius: 10, marginTop: 10 },
  historyHeading: { fontSize: 14, fontWeight: '700', marginBottom: 10 },
  historyRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1 },
  historyId: { fontSize: 13, fontWeight: '700' },
  historyItems: { fontSize: 11, marginTop: 2 },
  historyTotal: { fontSize: 14, fontWeight: '800', marginLeft: 10 },
});
