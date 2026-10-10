import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
} from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';

import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

const RANGES = [
  { key: 'today', label: 'Today' },
  { key: '7days', label: '7 Days' },
  { key: '30days', label: '30 Days' },
  { key: 'custom', label: 'Custom' },
];

export default function ReportsScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
  const [selectedRange, setSelectedRange] = useState('today');

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['reports', selectedRange],
    queryFn: () => apiClient<any>(`/reports?range=${selectedRange}`),
  });

  if (isLoading && !isRefetching) return <LoadingState message="Calculating analytics..." />;
  if (error) return <ErrorState message={error.message} onRetry={() => refetch()} />;

  const summary = data?.summary ?? {};
  const chartData = data?.chart_data ?? [];
  const topItems = data?.top_items ?? [];

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Screen 7 Header ── */}
      <View style={[styles.headerArea, { backgroundColor: theme.background }]}>
        <View style={styles.topTitleRow}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
            <Ionicons name="arrow-back" size={22} color="#112D27" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Reports & Analytics</Text>
          <View style={{ width: 38 }} />
        </View>

        {/* Range Filter Pills */}
        <View style={styles.rangeBar}>
          {RANGES.map((r) => {
            const active = selectedRange === r.key;
            return (
              <TouchableOpacity
                key={r.key}
                onPress={() => setSelectedRange(r.key)}
                style={[
                  styles.rangePill,
                  active ? styles.rangePillActive : styles.rangePillInactive,
                ]}
              >
                <Text
                  style={[
                    styles.rangePillText,
                    active ? styles.rangePillTextActive : styles.rangePillTextInactive,
                  ]}
                >
                  {r.label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
      >
        {/* ── 3-Column Metrics Card (Live Data) ── */}
        <View style={styles.statsCard}>
          <View style={styles.statCol}>
            <Text style={styles.statLabel}>Total Sales</Text>
            <Text style={styles.statValue}>
              Rs {Number(summary.total_revenue || 0).toLocaleString()}
            </Text>
            <View style={styles.statTrendRow}>
              <Ionicons name="stats-chart" size={12} color="#064E45" />
              <Text style={styles.statTrendText}>Live</Text>
            </View>
          </View>

          <View style={styles.statDivider} />

          <View style={styles.statCol}>
            <Text style={styles.statLabel}>Total Orders</Text>
            <Text style={styles.statValue}>{summary.total_orders ?? 0}</Text>
            <View style={styles.statTrendRow}>
              <Ionicons name="stats-chart" size={12} color="#064E45" />
              <Text style={styles.statTrendText}>Live</Text>
            </View>
          </View>

          <View style={styles.statDivider} />

          <View style={styles.statCol}>
            <Text style={styles.statLabel}>Avg. Order</Text>
            <Text style={styles.statValue}>
              Rs {Number(summary.aov || 0).toLocaleString()}
            </Text>
            <View style={styles.statTrendRow}>
              <Ionicons name="stats-chart" size={12} color="#064E45" />
              <Text style={styles.statTrendText}>Live</Text>
            </View>
          </View>
        </View>

        {/* ── Sales Overview Vertical Bar Chart Card ── */}
        <View style={styles.chartCard}>
          <Text style={styles.chartCardTitle}>Sales Overview</Text>
          <View style={styles.chartArea}>
            {chartData.length === 0 ? (
              <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
                <Text style={{ fontSize: 13, color: '#7E9188' }}>No sales data for this period</Text>
              </View>
            ) : (() => {
              const maxAmount = Math.max(...chartData.map((c: any) => Number(c.amount) || 0), 1);
              return chartData.map((bar: any, i: number) => {
                const amt = Number(bar.amount) || 0;
                const pct = maxAmount > 0 && amt > 0
                  ? Math.min(100, Math.max(10, Math.round((amt / maxAmount) * 100)))
                  : 4;
                return (
                  <View key={bar.date || i} style={styles.barCol}>
                    <View style={styles.barTrack}>
                      <View style={[styles.barFill, { height: `${pct}%` }]} />
                    </View>
                    <Text style={styles.barLabel} numberOfLines={1}>{bar.label || bar.date}</Text>
                  </View>
                );
              });
            })()}
          </View>
        </View>

        {/* ── Top Selling Items (Live DB Query) ── */}
        <View style={styles.topItemsCard}>
          <Text style={styles.topItemsTitle}>Top Selling Items</Text>
          {topItems.length === 0 ? (
            <View style={{ paddingVertical: 14, alignItems: 'center' }}>
              <Text style={{ fontSize: 13, color: '#7E9188', fontWeight: '500' }}>
                No dishes sold in this period yet.
              </Text>
            </View>
          ) : (
            topItems.map((it: any, idx: number) => (
              <View key={it.name || idx} style={styles.topItemRow}>
                <View style={styles.topItemLeft}>
                  <Text style={styles.topItemNumber}>{idx + 1}.</Text>
                  <Text style={styles.topItemName}>{it.name}</Text>
                </View>
                <Text style={styles.topItemQty}>{it.total_qty || 0}</Text>
              </View>
            ))
          )}
        </View>
      </ScrollView>
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
  rangeBar: { flexDirection: 'row', gap: 8, marginBottom: 4 },
  rangePill: { paddingHorizontal: 16, paddingVertical: 7, borderRadius: 18 },
  rangePillActive: { backgroundColor: '#064E45' },
  rangePillInactive: { backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#ECE9E0' },
  rangePillText: { fontSize: 12 },
  rangePillTextActive: { color: '#FFFFFF', fontWeight: '700' },
  rangePillTextInactive: { color: '#7E9188', fontWeight: '600' },
  scrollContent: { paddingHorizontal: 20, paddingBottom: 100 },
  statsCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    paddingVertical: 18,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: '#ECE9E0',
    marginBottom: 16,
    elevation: 2,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
  },
  statCol: { flex: 1, alignItems: 'flex-start' },
  statLabel: { fontSize: 11, color: '#7E9188', fontWeight: '500', marginBottom: 4 },
  statValue: { fontSize: 17, fontWeight: '800', color: '#112D27' },
  statTrendRow: { flexDirection: 'row', alignItems: 'center', gap: 2, marginTop: 3 },
  statTrendText: { fontSize: 11, fontWeight: '700', color: '#1E8E3E' },
  statDivider: { width: 1, height: 34, backgroundColor: '#ECE9E0', marginHorizontal: 10 },
  chartCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    padding: 16,
    marginBottom: 16,
    elevation: 1,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
  },
  chartCardTitle: { fontSize: 16, fontWeight: '800', color: '#112D27', marginBottom: 16 },
  chartArea: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-end', height: 140, paddingBottom: 6 },
  barCol: { alignItems: 'center', flex: 1 },
  barTrack: { height: 110, width: 14, backgroundColor: '#F1EFE9', borderRadius: 7, justifyContent: 'flex-end', overflow: 'hidden' },
  barFill: { width: '100%', backgroundColor: '#064E45', borderRadius: 7 },
  barLabel: { fontSize: 11, color: '#7E9188', marginTop: 8, fontWeight: '600' },
  topItemsCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    padding: 16,
    marginBottom: 16,
    elevation: 1,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
  },
  topItemsTitle: { fontSize: 16, fontWeight: '800', color: '#112D27', marginBottom: 12 },
  topItemRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: '#F5F3ED' },
  topItemLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  topItemNumber: { fontSize: 13, fontWeight: '700', color: '#7E9188' },
  topItemName: { fontSize: 14, fontWeight: '700', color: '#112D27' },
  topItemQty: { fontSize: 14, fontWeight: '800', color: '#112D27' },
});
