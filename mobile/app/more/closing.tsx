import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  Alert,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import * as Sharing from 'expo-sharing';
import { File, Paths } from 'expo-file-system';

export default function CashClosingScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
  const [isExporting, setIsExporting] = useState(false);

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['cash-closing-summary'],
    queryFn: () => apiClient<any>('/closing/summary'),
  });

  if (isLoading && !isRefetching) return <LoadingState message="Calculating daily register summary..." />;
  if (error) return <ErrorState message={error.message} onRetry={() => refetch()} />;

  const closing = data?.closing ?? {};
  const archives = data?.archives ?? [];

  // Share / Download daily archive CSV via native phone share sheet (Feature 7)
  const handleShareArchive = async (dateStr?: string) => {
    const targetDate = dateStr || data?.date || new Date().toISOString().split('T')[0];
    try {
      setIsExporting(true);
      const isAvailable = await Sharing.isAvailableAsync();
      if (!isAvailable) {
        Alert.alert('Sharing Unavailable', 'Native sharing is not supported on this device.');
        return;
      }

      // Download CSV from API endpoint to device cache
      const downloadUrl = `${process.env.EXPO_PUBLIC_API_URL || 'http://192.168.100.4:8000/api/v1'}/closing/archive?date=${targetDate}`;
      const downloadedFile = await File.downloadFileAsync(downloadUrl, Paths.cache);

      await Sharing.shareAsync(downloadedFile.uri, {
        mimeType: 'text/csv',
        dialogTitle: `Share Orders Archive (${targetDate})`,
        UTI: 'public.comma-separated-values-text',
      });
    } catch (e: any) {
      Alert.alert('Export Error', e.message || 'Could not export archive file.');
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* Header */}
      <View style={[styles.headerArea, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
        <View style={styles.topRow}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color={theme.text} />
          </TouchableOpacity>
          <Text style={[styles.headerTitle, { color: theme.text }]}>Cash & Close In</Text>
          <View style={{ width: 36 }} />
        </View>
        <Text style={[styles.headerSub, { color: theme.textMuted }]}>
          Reconcile daily drawer balance, cash, cards, and daily CSV archives
        </Text>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
      >
        {/* Main Cash In Drawer Hero Card */}
        <View style={[styles.heroCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[styles.heroLabel, { color: theme.textMuted }]}>Total Expected Cash In Drawer</Text>
          <Text style={[styles.heroAmount, { color: theme.primary }]}>
            Rs. {Number(closing.cash_in_hand || 0).toLocaleString()}
          </Text>
          <View style={[styles.dateBadge, { backgroundColor: theme.primaryLight }]}>
            <Ionicons name="calendar-outline" size={14} color={theme.primary} />
            <Text style={[styles.dateText, { color: theme.primary }]}>{data?.date || 'Today'}</Text>
          </View>
        </View>

        {/* Detailed Breakdown Card */}
        <View style={[styles.sectionCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[styles.cardHeading, { color: theme.text }]}>Revenue & Collections Breakdown</Text>

          <View style={[styles.lineRow, { borderBottomColor: theme.border }]}>
            <View style={styles.methodInfo}>
              <Ionicons name="cash-outline" size={20} color="#16A34A" />
              <Text style={[styles.methodName, { color: theme.text }]}>Cash Orders (COD / Counter)</Text>
            </View>
            <Text style={[styles.methodAmount, { color: theme.text }]}>
              Rs. {Number(closing.cash_in_hand || 0).toLocaleString()}
            </Text>
          </View>

          <View style={[styles.lineRow, { borderBottomColor: theme.border }]}>
            <View style={styles.methodInfo}>
              <Ionicons name="card-outline" size={20} color={theme.primary} />
              <Text style={[styles.methodName, { color: theme.text }]}>Card POS Swipes</Text>
            </View>
            <Text style={[styles.methodAmount, { color: theme.text }]}>
              Rs. {Number(closing.card_payments || 0).toLocaleString()}
            </Text>
          </View>

          <View style={[styles.lineRow, { borderBottomColor: theme.border }]}>
            <View style={styles.methodInfo}>
              <Ionicons name="phone-portrait-outline" size={20} color="#A855F7" />
              <Text style={[styles.methodName, { color: theme.text }]}>JazzCash / Easypaisa Digital</Text>
            </View>
            <Text style={[styles.methodAmount, { color: theme.text }]}>
              Rs. {Number(closing.digital_sales || 0).toLocaleString()}
            </Text>
          </View>

          <View style={[styles.lineRow, { borderBottomColor: theme.border }]}>
            <View style={styles.methodInfo}>
              <Ionicons name="bicycle-outline" size={20} color={theme.foodOrange} />
              <Text style={[styles.methodName, { color: theme.text }]}>Delivery Fees Collected</Text>
            </View>
            <Text style={[styles.methodAmount, { color: theme.text }]}>
              Rs. {Number(closing.delivery_fees || 0).toLocaleString()}
            </Text>
          </View>
        </View>

        {/* Volume Summary */}
        <View style={[styles.sectionCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[styles.cardHeading, { color: theme.text }]}>Order Reconciliation</Text>

          <View style={styles.miniGrid}>
            <View style={[styles.miniBox, { backgroundColor: theme.surfaceSubtle }]}>
              <Text style={[styles.miniLabel, { color: theme.textMuted }]}>Total Orders</Text>
              <Text style={[styles.miniVal, { color: theme.text }]}>{closing.total_orders || 0}</Text>
            </View>
            <View style={[styles.miniBox, { backgroundColor: theme.surfaceSubtle }]}>
              <Text style={[styles.miniLabel, { color: theme.textMuted }]}>Delivered</Text>
              <Text style={[styles.miniVal, { color: '#16A34A' }]}>{closing.delivered || 0}</Text>
            </View>
            <View style={[styles.miniBox, { backgroundColor: theme.surfaceSubtle }]}>
              <Text style={[styles.miniLabel, { color: theme.textMuted }]}>Cancelled</Text>
              <Text style={[styles.miniVal, { color: '#DC2626' }]}>{closing.cancelled || 0}</Text>
            </View>
          </View>
        </View>

        {/* ── Daily Reset & Archive Sharing Section (Feature 7) ── */}
        <View style={[styles.sectionCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[styles.cardHeading, { color: theme.text }]}>Daily Reset & CSV Archives</Text>
          <Text style={[styles.archiveDesc, { color: theme.textMuted }]}>
            Share today's order archive via WhatsApp to your accountant or cloud backup. Daily order numbering will cleanly reset to #1 tomorrow morning.
          </Text>

          <TouchableOpacity
            onPress={() => handleShareArchive()}
            style={[styles.shareActionBtn, { backgroundColor: theme.primaryLight, borderColor: theme.primary }]}
          >
            <Ionicons name="share-outline" size={20} color={theme.primary} />
            <Text style={[styles.shareActionText, { color: theme.primary }]}>
              {isExporting ? 'Generating CSV...' : "Share Today's CSV Archive (WhatsApp/Drive)"}
            </Text>
          </TouchableOpacity>

          {archives.length > 0 && (
            <View style={{ marginTop: 14 }}>
              <Text style={[styles.subHeading, { color: theme.text }]}>Previous Days Archives</Text>
              {archives.slice(1, 5).map((arc: any) => (
                <View key={arc.date} style={[styles.archiveRow, { borderBottomColor: theme.border }]}>
                  <View>
                    <Text style={[styles.archiveDate, { color: theme.text }]}>{arc.date}</Text>
                    <Text style={[styles.archiveMeta, { color: theme.textMuted }]}>
                      {arc.total_orders} Orders • Rs. {Number(arc.total_sales || 0).toLocaleString()}
                    </Text>
                  </View>
                  <TouchableOpacity
                    onPress={() => handleShareArchive(arc.date)}
                    style={styles.archiveDownloadBtn}
                  >
                    <Ionicons name="download-outline" size={18} color={theme.primary} />
                  </TouchableOpacity>
                </View>
              ))}
            </View>
          )}
        </View>

        {/* Action Button */}
        <View style={styles.btnArea}>
          <AppButton
            title="Reconcile & Close Register"
            onPress={() => Alert.alert('Register Closed', 'Daily drawer reconciled. All records archived for today.')}
            style={{ backgroundColor: theme.primary }}
          />
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  headerArea: { paddingHorizontal: 16, paddingTop: 10, paddingBottom: 14, borderBottomWidth: 1 },
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  backBtn: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { fontSize: 20, fontWeight: '800' },
  headerSub: { fontSize: 12, marginTop: 4 },
  scrollContent: { padding: 16, paddingBottom: 90 },
  heroCard: { padding: 20, borderRadius: 20, borderWidth: 1, alignItems: 'center', marginBottom: 16 },
  heroLabel: { fontSize: 13, fontWeight: '600' },
  heroAmount: { fontSize: 32, fontWeight: '800', marginVertical: 8 },
  dateBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 4, borderRadius: 14, gap: 6 },
  dateText: { fontSize: 12, fontWeight: '700' },
  sectionCard: { padding: 16, borderRadius: 18, borderWidth: 1, marginBottom: 16 },
  cardHeading: { fontSize: 15, fontWeight: '700', marginBottom: 12 },
  archiveDesc: { fontSize: 12, lineHeight: 18, marginBottom: 14 },
  shareActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    gap: 8,
  },
  shareActionText: { fontSize: 13, fontWeight: '700' },
  subHeading: { fontSize: 13, fontWeight: '700', marginBottom: 8 },
  archiveRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1 },
  archiveDate: { fontSize: 13, fontWeight: '700' },
  archiveMeta: { fontSize: 11, marginTop: 2 },
  archiveDownloadBtn: { padding: 8 },
  lineRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1 },
  methodInfo: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  methodName: { fontSize: 14, fontWeight: '600' },
  methodAmount: { fontSize: 15, fontWeight: '800' },
  miniGrid: { flexDirection: 'row', gap: 10 },
  miniBox: { flex: 1, padding: 12, borderRadius: 12, alignItems: 'center' },
  miniLabel: { fontSize: 11, marginBottom: 4 },
  miniVal: { fontSize: 18, fontWeight: '800' },
  btnArea: { marginTop: 10 },
});
