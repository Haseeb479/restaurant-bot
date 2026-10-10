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

const RANGES = [
  { key: 'today', label: 'Today' },
  { key: '7days', label: 'Last 7 Days' },
  { key: '30days', label: 'Last 30 Days' },
];

export default function ReportsScreen() {
  const { theme } = useAppTheme();
  const [selectedRange, setSelectedRange] = useState('7days');

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
    <ScrollView
      style={[styles.container, { backgroundColor: theme.background }]}
      refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
    >
      <View style={styles.header}>
        <Text style={[styles.title, { color: theme.text }]}>Reports & Analytics</Text>
        <Text style={[styles.sub, { color: theme.textMuted }]}>
          Revenue metrics, order trends, and top sellers
        </Text>
      </View>

      {/* Range Filter Pills */}
      <View style={styles.rangeBar}>
        {RANGES.map((r) => (
          <TouchableOpacity
            key={r.key}
            onPress={() => setSelectedRange(r.key)}
            style={[
              styles.pill,
              selectedRange === r.key && { backgroundColor: theme.primary },
            ]}
          >
            <Text
              style={[
                styles.pillText,
                { color: selectedRange === r.key ? '#FFFFFF' : theme.textMuted },
              ]}
            >
              {r.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Summary KPI Cards */}
      <View style={styles.summaryGrid}>
        <View style={[styles.summaryCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[styles.kpiLabel, { color: theme.textMuted }]}>Total Revenue</Text>
          <Text style={[styles.kpiValue, { color: theme.primary }]}>
            Rs. {Number(summary.total_revenue || 0).toLocaleString()}
          </Text>
        </View>

        <View style={[styles.summaryCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[styles.kpiLabel, { color: theme.textMuted }]}>Total Orders</Text>
          <Text style={[styles.kpiValue, { color: theme.text }]}>
            {summary.total_orders || 0}
          </Text>
        </View>

        <View style={[styles.summaryCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[styles.kpiLabel, { color: theme.textMuted }]}>Avg Order (AOV)</Text>
          <Text style={[styles.kpiValue, { color: theme.text }]}>
            Rs. {Number(summary.aov || 0).toLocaleString()}
          </Text>
        </View>

        <View style={[styles.summaryCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[styles.kpiLabel, { color: theme.textMuted }]}>Delivered</Text>
          <Text style={[styles.kpiValue, { color: theme.success }]}>
            {summary.completed || 0}
          </Text>
        </View>
      </View>

      {/* Revenue Timeline Breakdown */}
      <View style={[styles.sectionCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
        <Text style={[styles.sectionTitle, { color: theme.text }]}>Daily Revenue Breakdown</Text>
        {chartData.map((d: any) => (
          <View key={d.date} style={[styles.chartRow, { borderBottomColor: theme.border }]}>
            <Text style={[styles.dateLabel, { color: theme.text }]}>{d.label}</Text>
            <Text style={[styles.dateAmount, { color: theme.primary }]}>
              Rs. {Number(d.amount).toLocaleString()}
            </Text>
          </View>
        ))}
      </View>

      {/* Top Performing Items */}
      <View style={[styles.sectionCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
        <Text style={[styles.sectionTitle, { color: theme.text }]}>Top Selling Items</Text>
        {topItems.length === 0 ? (
          <Text style={[styles.emptyNotice, { color: theme.textMuted }]}>
            No items sold during this period.
          </Text>
        ) : (
          topItems.map((it: any, idx: number) => (
            <View key={idx} style={[styles.topItemRow, { borderBottomColor: theme.border }]}>
              <View>
                <Text style={[styles.topItemName, { color: theme.text }]}>{it.name}</Text>
                <Text style={[styles.topItemSub, { color: theme.textMuted }]}>
                  {it.total_qty} units sold
                </Text>
              </View>
              <Text style={[styles.topItemRev, { color: theme.text }]}>
                Rs. {Number(it.total_revenue).toLocaleString()}
              </Text>
            </View>
          ))
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 16 },
  header: { marginBottom: 14 },
  title: { fontSize: 22, fontWeight: '700' },
  sub: { fontSize: 13, marginTop: 4 },
  rangeBar: { flexDirection: 'row', gap: 8, marginBottom: 16 },
  pill: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
  },
  pillText: { fontSize: 13, fontWeight: '600' },
  summaryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 16 },
  summaryCard: { width: '48%', padding: 12, borderRadius: 10, borderWidth: 1 },
  kpiLabel: { fontSize: 12, marginBottom: 4 },
  kpiValue: { fontSize: 18, fontWeight: '700' },
  sectionCard: { padding: 16, borderRadius: 12, borderWidth: 1, marginBottom: 16 },
  sectionTitle: { fontSize: 16, fontWeight: '700', marginBottom: 12 },
  chartRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
    borderBottomWidth: 1,
  },
  dateLabel: { fontSize: 13 },
  dateAmount: { fontSize: 13, fontWeight: '700' },
  emptyNotice: { fontSize: 13, textAlign: 'center', marginVertical: 10 },
  topItemRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: 1,
  },
  topItemName: { fontSize: 14, fontWeight: '600' },
  topItemSub: { fontSize: 12, marginTop: 2 },
  topItemRev: { fontSize: 14, fontWeight: '700' },
});
