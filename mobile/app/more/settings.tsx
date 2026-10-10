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
  TouchableOpacity,
} from 'react-native';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';

import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';

export default function StoreSettingsScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
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
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Screen 8 Header ── */}
      <View style={[styles.headerArea, { backgroundColor: theme.background }]}>
        <View style={styles.topTitleRow}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
            <Ionicons name="arrow-back" size={22} color="#112D27" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Restaurant Settings</Text>
          <View style={{ width: 38 }} />
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
      >
        {/* ── Restaurant Brand Card (Screen 8) ── */}
        <View style={styles.restaurantHeroCard}>
          <View style={styles.heroLeft}>
            <View style={styles.restaurantAvatarBox}>
              <Ionicons name="storefront" size={24} color="#064E45" />
            </View>
            <View style={{ marginLeft: 12 }}>
              <Text style={styles.restaurantHeroName}>
                {settings.restaurant_name || 'Pizza Palace'}
              </Text>
              <Text style={styles.restaurantHeroHours}>
                Open • 10:00 AM - 11:00 PM
              </Text>
            </View>
          </View>

          <View style={[styles.onlinePill, { backgroundColor: isOpen ? '#E6F4EA' : '#FCE8E6' }]}>
            <View style={[styles.onlineDot, { backgroundColor: isOpen ? '#1E8E3E' : '#D93025' }]} />
            <Text style={[styles.onlineText, { color: isOpen ? '#1E8E3E' : '#D93025' }]}>
              {isOpen ? 'Online' : 'Offline'}
            </Text>
          </View>
        </View>

        {/* ── Settings Menu Navigation Rows (Screen 8) ── */}
        <View style={styles.settingsMenuCard}>
          <TouchableOpacity
            style={styles.settingRow}
            onPress={() => router.push('/(app)/more')}
          >
            <View style={styles.settingRowLeft}>
              <Ionicons name="person-outline" size={20} color="#064E45" />
              <Text style={styles.settingRowLabel}>Restaurant Profile</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color="#7E9188" />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.settingRow}
            onPress={() => router.push('/more/settings')}
          >
            <View style={styles.settingRowLeft}>
              <Ionicons name="time-outline" size={20} color="#064E45" />
              <Text style={styles.settingRowLabel}>Opening Hours</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color="#7E9188" />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.settingRow}
            onPress={() => router.push('/(app)/delivery')}
          >
            <View style={styles.settingRowLeft}>
              <Ionicons name="bicycle-outline" size={20} color="#064E45" />
              <Text style={styles.settingRowLabel}>Delivery Settings</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color="#7E9188" />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.settingRow}
            onPress={() => router.push('/(app)/pos')}
          >
            <View style={styles.settingRowLeft}>
              <Ionicons name="card-outline" size={20} color="#064E45" />
              <Text style={styles.settingRowLabel}>Payment Methods</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color="#7E9188" />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.settingRow}
            onPress={() => router.push('/more/chat-oversight')}
          >
            <View style={styles.settingRowLeft}>
              <Ionicons name="logo-whatsapp" size={20} color="#25D366" />
              <Text style={styles.settingRowLabel}>WhatsApp Integration</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color="#7E9188" />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.settingRow}
            onPress={() => router.push('/more/reports')}
          >
            <View style={styles.settingRowLeft}>
              <Ionicons name="notifications-outline" size={20} color="#064E45" />
              <Text style={styles.settingRowLabel}>Notifications</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color="#7E9188" />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.settingRow}
            onPress={() => router.push('/(app)/more')}
          >
            <View style={styles.settingRowLeft}>
              <Ionicons name="people-outline" size={20} color="#064E45" />
              <Text style={styles.settingRowLabel}>Staff Management</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color="#7E9188" />
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.settingRow, { borderBottomWidth: 0 }]}
            onPress={() => router.push('/(app)/more')}
          >
            <View style={styles.settingRowLeft}>
              <Ionicons name="settings-outline" size={20} color="#064E45" />
              <Text style={styles.settingRowLabel}>General Settings</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color="#7E9188" />
          </TouchableOpacity>
        </View>

        {/* ── Operational Quick Parameters ── */}
        <View style={styles.formCard}>
          <Text style={styles.sectionTitle}>Operating Parameters</Text>
          <View style={styles.switchRow}>
            <Text style={styles.label}>Accepting Orders</Text>
            <Switch
              value={isOpen}
              onValueChange={setIsOpen}
              trackColor={{ false: '#EF4444', true: '#064E45' }}
            />
          </View>

          <Text style={styles.label}>Delivery Charge (Rs.)</Text>
          <TextInput
            value={deliveryCharge}
            onChangeText={setDeliveryCharge}
            keyboardType="numeric"
            style={styles.input}
          />

          <Text style={styles.label}>Minimum Order (Rs.)</Text>
          <TextInput
            value={minOrder}
            onChangeText={setMinOrder}
            keyboardType="numeric"
            style={styles.input}
          />

          <Text style={styles.label}>Delivery Radius (KM)</Text>
          <TextInput
            value={radius}
            onChangeText={setRadius}
            keyboardType="numeric"
            style={styles.input}
          />

          <Text style={styles.label}>Store Address</Text>
          <TextInput
            value={address}
            onChangeText={setAddress}
            style={styles.input}
          />

          <AppButton
            title="Save Operating Rules"
            loading={updateMutation.isPending}
            onPress={handleSave}
            style={styles.saveBtn}
          />
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
  scrollContent: { paddingHorizontal: 20, paddingBottom: 100 },
  restaurantHeroCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
    elevation: 2,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
  },
  heroLeft: { flexDirection: 'row', alignItems: 'center', flex: 1 },
  restaurantAvatarBox: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: '#E6F0EC',
    alignItems: 'center',
    justifyContent: 'center',
  },
  restaurantHeroName: { fontSize: 16, fontWeight: '800', color: '#112D27' },
  restaurantHeroHours: { fontSize: 12, color: '#7E9188', marginTop: 2 },
  onlinePill: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20, gap: 5 },
  onlineDot: { width: 7, height: 7, borderRadius: 3.5 },
  onlineText: { fontSize: 12, fontWeight: '700' },
  settingsMenuCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    paddingHorizontal: 16,
    paddingVertical: 8,
    marginBottom: 16,
    elevation: 1,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
  },
  settingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#F5F3ED',
  },
  settingRowLeft: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  settingRowLabel: { fontSize: 14, fontWeight: '700', color: '#112D27' },
  formCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    padding: 18,
    marginBottom: 20,
  },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: '#112D27', marginBottom: 12 },
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  label: { fontSize: 12, fontWeight: '700', color: '#112D27', marginTop: 10, marginBottom: 6 },
  input: {
    height: 42,
    borderWidth: 1,
    borderColor: '#ECE9E0',
    borderRadius: 12,
    paddingHorizontal: 12,
    fontSize: 13,
    color: '#112D27',
    backgroundColor: '#F8F6F0',
  },
  saveBtn: { marginTop: 18 },
});
