import React from 'react';
import { View, Text, StyleSheet, ViewStyle } from 'react-native';

interface StatusBadgeProps {
  status: 'pending' | 'confirmed' | 'preparing' | 'ready' | 'out_for_delivery' | 'delivered' | 'cancelled';
  style?: ViewStyle;
}

const statusMap: Record<string, { label: string; color: string; bg: string }> = {
  pending: { label: 'New Order', color: '#FF941F', bg: '#FFF0DE' },
  confirmed: { label: 'Confirmed', color: '#FF941F', bg: '#FFF5EB' },
  preparing: { label: 'Preparing', color: '#FF941F', bg: '#FFF0DE' },
  ready: { label: 'Ready for Pickup', color: '#064E45', bg: '#E8F5F2' },
  out_for_delivery: { label: 'Out for Delivery', color: '#003C35', bg: '#E8F5F2' },
  delivered: { label: 'Delivered', color: '#059669', bg: '#DCFCE7' },
  cancelled: { label: 'Cancelled', color: '#DC2626', bg: '#FEE2E2' },
};

export const StatusBadge: React.FC<StatusBadgeProps> = ({ status, style }) => {
  const meta = statusMap[status] ?? { label: status, color: '#6B7280', bg: '#F3F4F6' };

  return (
    <View style={[styles.badge, { backgroundColor: meta.bg }, style]}>
      <Text style={[styles.text, { color: meta.color }]}>{meta.label}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: -0.2,
  },
});
