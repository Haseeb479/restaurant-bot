import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  Switch,
  Alert,
  RefreshControl,
} from 'react-native';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';

export default function StoreSettingsScreen() {
  const { theme } = useAppTheme();
  const queryClient = useQueryClient();

  const [deliveryCharge, setDeliveryCharge] = useState('');
  const [minOrder, setMinOrder] = useState('');
  const [radius, setRadius] = useState('');
  const [address, setAddress] = useState('');
  const [isOpen, setIsOpen] = useState(true);

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['store-settings'],
    queryFn: () => apiClient<any>('/settings'),
  });

  useEffect(() => {
    if (data?.settings) {
      setDeliveryCharge(String(data.settings.delivery_charge ?? 0));
      setMinOrder(String(data.settings.minimum_order ?? 0));
      setRadius(String(data.settings.delivery_radius_km ?? 5));
      setAddress(data.settings.address ?? '');
      setIsOpen(Boolean(data.settings.is_open));
    }
  }, [data]);

  const updateMutation = useMutation({
    mutationFn: (payload: any) =>
      apiClient('/settings', {
        method: 'PATCH',
        body: JSON.stringify(payload),
      }),
    onSuccess: () => {
      Alert.alert('Settings Saved', 'Your store settings have been updated.');
      queryClient.invalidateQueries({ queryKey: ['store-settings'] });
      queryClient.invalidateQueries({ queryKey: ['command-center'] });
    },
    onError: (err: any) => {
      Alert.alert('Error', err.message || 'Failed to update settings.');
    },
  });

  if (isLoading && !isRefetching) return <LoadingState message="Loading settings..." />;
  if (error) return <ErrorState message={error.message} onRetry={() => refetch()} />;

  const settings = data?.settings ?? {};

  const handleSave = () => {
    updateMutation.mutate({
      delivery_charge: Number(deliveryCharge),
      minimum_order: Number(minOrder),
      delivery_radius_km: Number(radius),
      address: address.trim(),
      is_open: isOpen,
    });
  };

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: theme.background }]}
      refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
    >
      <View style={styles.header}>
        <Text style={[styles.title, { color: theme.text }]}>Store Settings</Text>
        <Text style={[styles.sub, { color: theme.textMuted }]}>
          Operating configuration, delivery pricing, and bot connection
        </Text>
      </View>

      {/* WhatsApp Bot Status Banner */}
      <View style={[styles.botCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
        <View style={styles.botRow}>
          <Ionicons
            name="logo-whatsapp"
            size={24}
            color={settings.bot_status === 'connected' ? '#25D366' : theme.textMuted}
          />
          <View style={{ flex: 1, marginLeft: 10 }}>
            <Text style={[styles.botTitle, { color: theme.text }]}>WhatsApp Bot Status</Text>
            <Text style={[styles.botSub, { color: theme.textMuted }]}>
              {settings.whatsapp_number ? `+${settings.whatsapp_number}` : 'Unconnected'}
            </Text>
          </View>
          <View
            style={[
              styles.statusPill,
              {
                backgroundColor:
                  settings.bot_status === 'connected' ? '#DCFCE7' : '#FEE2E2',
              },
            ]}
          >
            <Text
              style={[
                styles.statusPillText,
                {
                  color:
                    settings.bot_status === 'connected' || settings.bot_status === 'open' ? '#16A34A' : '#DC2626',
                },
              ]}
            >
              {settings.bot_status === 'connected' || settings.bot_status === 'open' ? 'ONLINE' : 'OFFLINE'}
            </Text>
          </View>
        </View>
      </View>

      {/* Settings Form */}
      <View style={[styles.formCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
        <Text style={[styles.sectionTitle, { color: theme.text }]}>Store Availability</Text>
        <View style={styles.switchRow}>
          <Text style={[styles.label, { color: theme.text }]}>Accepting Orders</Text>
          <Switch
            value={isOpen}
            onValueChange={setIsOpen}
            trackColor={{ false: theme.border, true: theme.primary }}
          />
        </View>

        <View style={[styles.divider, { backgroundColor: theme.border }]} />

        <Text style={[styles.sectionTitle, { color: theme.text }]}>Delivery Rules</Text>

        <Text style={[styles.label, { color: theme.text }]}>Delivery Charge (Rs.)</Text>
        <TextInput
          value={deliveryCharge}
          onChangeText={setDeliveryCharge}
          keyboardType="numeric"
          style={[styles.input, { color: theme.text, borderColor: theme.border }]}
        />

        <Text style={[styles.label, { color: theme.text }]}>Minimum Order (Rs.)</Text>
        <TextInput
          value={minOrder}
          onChangeText={setMinOrder}
          keyboardType="numeric"
          style={[styles.input, { color: theme.text, borderColor: theme.border }]}
        />

        <Text style={[styles.label, { color: theme.text }]}>Delivery Radius (KM)</Text>
        <TextInput
          value={radius}
          onChangeText={setRadius}
          keyboardType="numeric"
          style={[styles.input, { color: theme.text, borderColor: theme.border }]}
        />

        <Text style={[styles.label, { color: theme.text }]}>Store Address</Text>
        <TextInput
          value={address}
          onChangeText={setAddress}
          style={[styles.input, { color: theme.text, borderColor: theme.border }]}
        />

        <AppButton
          title="Save Changes"
          loading={updateMutation.isPending}
          onPress={handleSave}
          style={styles.saveBtn}
        />
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 16 },
  header: { marginBottom: 14 },
  title: { fontSize: 22, fontWeight: '700' },
  sub: { fontSize: 13, marginTop: 4 },
  botCard: { padding: 14, borderRadius: 12, borderWidth: 1, marginBottom: 14 },
  botRow: { flexDirection: 'row', alignItems: 'center' },
  botTitle: { fontSize: 14, fontWeight: '700' },
  botSub: { fontSize: 12, marginTop: 2 },
  statusPill: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  statusPillText: { fontSize: 11, fontWeight: '800' },
  formCard: { padding: 16, borderRadius: 12, borderWidth: 1, marginBottom: 32 },
  sectionTitle: { fontSize: 15, fontWeight: '700', marginBottom: 10 },
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  divider: { height: 1, marginVertical: 14 },
  label: { fontSize: 13, fontWeight: '600', marginBottom: 6, marginTop: 8 },
  input: {
    height: 44,
    borderWidth: 1,
    borderRadius: 8,
    paddingHorizontal: 12,
    fontSize: 14,
    marginBottom: 4,
  },
  saveBtn: { marginTop: 16 },
});
