import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Image,
  TextInput,
  TouchableOpacity,
  Alert,
  Modal,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { useAuthStore } from '../../stores/authStore';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';

export default function MoreScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
  const logout = useAuthStore((s) => s.logout);
  const queryClient = useQueryClient();

  const [isQrModalOpen, setIsQrModalOpen] = useState(false);
  const [isPasswordModalOpen, setIsPasswordModalOpen] = useState(false);
  const [currentPw, setCurrentPw] = useState('');
  const [newPw, setNewPw] = useState('');

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['owner-profile'],
    queryFn: () => apiClient<any>('/profile'),
  });

  const { data: qrData, isLoading: qrLoading, refetch: refetchQr } = useQuery({
    queryKey: ['bot-qr-code'],
    queryFn: () => apiClient<any>('/profile/bot-qr'),
    enabled: isQrModalOpen,
  });

  const restartBotMutation = useMutation({
    mutationFn: () => apiClient('/profile/bot-restart', { method: 'POST' }),
    onSuccess: () => {
      Alert.alert('Bot Restarted', 'WhatsApp instance restarted. Reloading status...');
      queryClient.invalidateQueries({ queryKey: ['owner-profile'] });
    },
  });

  const updatePasswordMutation = useMutation({
    mutationFn: (payload: any) =>
      apiClient('/profile/password', {
        method: 'POST',
        body: JSON.stringify(payload),
      }),
    onSuccess: () => {
      setIsPasswordModalOpen(false);
      setCurrentPw('');
      setNewPw('');
      Alert.alert('Password Updated', 'Your owner password has been successfully updated.');
    },
    onError: (err: any) => {
      Alert.alert('Update Failed', err.message || 'Could not change password.');
    },
  });

  if (isLoading) return <LoadingState message="Loading owner profile..." />;
  if (error) return <ErrorState message={error.message} onRetry={() => refetch()} />;

  const profile = data?.profile ?? {};
  const isBotConnected = profile.bot_status === 'connected' || profile.bot_status === 'open';

  const handleLogout = () => {
    Alert.alert('Sign Out', 'Are you sure you want to log out of Grillcafe POS?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Sign Out', style: 'destructive', onPress: () => logout() },
    ]);
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* Header */}
      <View style={[styles.headerArea, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
        <Text style={[styles.headerTitle, { color: theme.text }]}>Settings & Operations</Text>
        <Text style={[styles.headerSub, { color: theme.textMuted }]}>
          Restaurant profile, WhatsApp bot pairing & security
        </Text>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Profile Card */}
        <View style={[styles.profileCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <View style={[styles.avatarWrap, { backgroundColor: theme.primary }]}>
            <Text style={styles.avatarLetter}>{(profile.name || 'G')[0].toUpperCase()}</Text>
          </View>
          <View style={{ flex: 1, marginLeft: 14 }}>
            <Text style={[styles.profileName, { color: theme.text }]}>{profile.name || 'Grill Cafe'}</Text>
            <Text style={[styles.profileDetail, { color: theme.textMuted }]}>
              📞 {profile.whatsapp_number || profile.owner_phone || 'WhatsApp Connected'}
            </Text>
            <Text style={[styles.profileDetail, { color: theme.textMuted }]}>
              📍 {profile.city || 'Main Branch'} • {profile.address || 'Dining & Takeaway'}
            </Text>
          </View>
        </View>

        {/* WhatsApp Bot Connectivity Section */}
        <View style={[styles.sectionCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <View style={styles.sectionHeader}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
              <Ionicons name="logo-whatsapp" size={22} color={isBotConnected ? '#22C55E' : theme.textMuted} />
              <Text style={[styles.sectionTitle, { color: theme.text }]}>WhatsApp Bot Engine</Text>
            </View>
            <View
              style={[
                styles.botPill,
                { backgroundColor: isBotConnected ? '#DCFCE7' : '#FEE2E2' },
              ]}
            >
              <Text
                style={[
                  styles.botPillText,
                  { color: isBotConnected ? '#16A34A' : '#DC2626' },
                ]}
              >
                {isBotConnected ? 'ONLINE' : 'PAUSED'}
              </Text>
            </View>
          </View>

          <Text style={[styles.botDesc, { color: theme.textMuted }]}>
            {isBotConnected
              ? 'Automated ordering engine is processing incoming WhatsApp chat orders directly into your kitchen.'
              : 'Scan the pairing QR code to link your phone WhatsApp number to the bot engine.'}
          </Text>

          <View style={styles.botActionRow}>
            <TouchableOpacity
              onPress={() => setIsQrModalOpen(true)}
              style={[styles.botActionBtn, { backgroundColor: theme.primary }]}
            >
              <Ionicons name="qr-code" size={16} color="#FFFFFF" />
              <Text style={styles.botActionText}>Pair / Scan QR</Text>
            </TouchableOpacity>

            <TouchableOpacity
              onPress={() => restartBotMutation.mutate()}
              style={[styles.botActionBtn, { backgroundColor: theme.surfaceSubtle }]}
            >
              <Ionicons name="reload" size={16} color={theme.text} />
              <Text style={[styles.botActionText, { color: theme.text }]}>Restart Engine</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Operational Shortcuts Menu */}
        <View style={[styles.menuListCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <TouchableOpacity
            onPress={() => router.push('/more/chat-oversight')}
            style={[styles.menuRow, { borderBottomColor: theme.border }]}
          >
            <View style={[styles.menuIconWrap, { backgroundColor: '#DCFCE7' }]}>
              <Ionicons name="chatbubbles" size={18} color="#16A34A" />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.menuTitle, { color: theme.text }]}>Live Bot Chats & Takeover</Text>
              <Text style={[styles.menuSub, { color: theme.textMuted }]}>Real-time WhatsApp chats & human pause</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.textMuted} />
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/(app)/delivery')}
            style={[styles.menuRow, { borderBottomColor: theme.border }]}
          >
            <View style={[styles.menuIconWrap, { backgroundColor: '#E0E7FF' }]}>
              <Ionicons name="bicycle" size={18} color="#4F46E5" />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.menuTitle, { color: theme.text }]}>Rider Fleet & Dispatch</Text>
              <Text style={[styles.menuSub, { color: theme.textMuted }]}>Assign drivers, GPS navigation & slips</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.textMuted} />
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/(app)/menu')}
            style={[styles.menuRow, { borderBottomColor: theme.border }]}
          >
            <View style={[styles.menuIconWrap, { backgroundColor: '#E8F5F2' }]}>
              <Ionicons name="restaurant" size={18} color="#064E45" />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.menuTitle, { color: theme.text }]}>Menu Management & 86ing</Text>
              <Text style={[styles.menuSub, { color: theme.textMuted }]}>Catalog pricing, 86 toggles & CSV/photo upload</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.textMuted} />
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/more/customers')}
            style={[styles.menuRow, { borderBottomColor: theme.border }]}
          >
            <View style={[styles.menuIconWrap, { backgroundColor: '#FAF5FF' }]}>
              <Ionicons name="people" size={18} color="#A855F7" />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.menuTitle, { color: theme.text }]}>Customers Directory & Broadcast</Text>
              <Text style={[styles.menuSub, { color: theme.textMuted }]}>CRM profiles, LTV & WhatsApp marketing</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.textMuted} />
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/more/closing')}
            style={[styles.menuRow, { borderBottomColor: theme.border }]}
          >
            <View style={[styles.menuIconWrap, { backgroundColor: '#FFF7ED' }]}>
              <Ionicons name="calculator" size={18} color="#F97316" />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.menuTitle, { color: theme.text }]}>Cash & Drawer Closing</Text>
              <Text style={[styles.menuSub, { color: theme.textMuted }]}>Daily shift balance & cash log</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.textMuted} />
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/more/reports')}
            style={[styles.menuRow, { borderBottomColor: theme.border }]}
          >
            <View style={[styles.menuIconWrap, { backgroundColor: '#F0FDF4' }]}>
              <Ionicons name="bar-chart" size={18} color="#22C55E" />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.menuTitle, { color: theme.text }]}>Reports & Analytics</Text>
              <Text style={[styles.menuSub, { color: theme.textMuted }]}>Sales trends & top dishes</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.textMuted} />
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => router.push('/more/settings')}
            style={[styles.menuRow, { borderBottomColor: theme.border }]}
          >
            <View style={[styles.menuIconWrap, { backgroundColor: theme.surfaceSubtle }]}>
              <Ionicons name="cog" size={18} color={theme.text} />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.menuTitle, { color: theme.text }]}>Restaurant Operating Rules</Text>
              <Text style={[styles.menuSub, { color: theme.textMuted }]}>Delivery fees & store timing</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.textMuted} />
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setIsPasswordModalOpen(true)}
            style={styles.menuRow}
          >
            <View style={[styles.menuIconWrap, { backgroundColor: '#FEE2E2' }]}>
              <Ionicons name="key" size={18} color="#DC2626" />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.menuTitle, { color: theme.text }]}>Change Password</Text>
              <Text style={[styles.menuSub, { color: theme.textMuted }]}>Update manager sign-in password</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.textMuted} />
          </TouchableOpacity>
        </View>

        {/* Logout Button */}
        <View style={styles.logoutWrap}>
          <AppButton
            title="Sign Out of Grillcafe POS"
            variant="outline"
            onPress={handleLogout}
            style={{ borderColor: theme.danger }}
            textStyle={{ color: theme.danger }}
          />
        </View>
      </ScrollView>

      {/* ── WhatsApp QR Pairing Modal ── */}
      <Modal visible={isQrModalOpen} animationType="slide" transparent onRequestClose={() => setIsQrModalOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.qrModalCard, { backgroundColor: theme.surface }]}>
            <View style={styles.modalHeader}>
              <Text style={[styles.modalTitle, { color: theme.text }]}>WhatsApp QR Pairing</Text>
              <TouchableOpacity onPress={() => setIsQrModalOpen(false)}>
                <Ionicons name="close-circle" size={26} color={theme.textMuted} />
              </TouchableOpacity>
            </View>

            <Text style={[styles.qrInstructions, { color: theme.textMuted }]}>
              Open WhatsApp on your phone → Linked Devices → Link a Device and point the camera at this code:
            </Text>

            <View style={[styles.qrContainer, { backgroundColor: '#FFFFFF', borderColor: theme.border }]}>
              {qrLoading ? (
                <Text style={{ color: theme.textMuted }}>Generating pairing code...</Text>
              ) : qrData?.qr ? (
                <Image
                  source={{ uri: qrData.qr }}
                  style={{ width: 220, height: 220 }}
                  resizeMode="contain"
                />
              ) : (
                <View style={{ alignItems: 'center', padding: 20 }}>
                  <Ionicons name="checkmark-circle" size={48} color="#16A34A" />
                  <Text style={[styles.qrSuccessText, { color: theme.text }]}>
                    {qrData?.message || 'Instance already paired & online!'}
                  </Text>
                </View>
              )}
            </View>

            <AppButton
              title="Refresh QR Code"
              onPress={() => refetchQr()}
              variant="secondary"
              style={{ marginTop: 16 }}
            />
          </View>
        </View>
      </Modal>

      {/* ── Change Password Modal ── */}
      <Modal visible={isPasswordModalOpen} animationType="fade" transparent onRequestClose={() => setIsPasswordModalOpen(false)}>
        <View style={styles.modalBackdropCenter}>
          <View style={[styles.pwModalCard, { backgroundColor: theme.surface }]}>
            <Text style={[styles.modalTitle, { color: theme.text }]}>Update Password</Text>
            <Text style={[styles.qrInstructions, { color: theme.textMuted }]}>
              Enter your current password followed by your new password:
            </Text>

            <TextInput
              placeholder="Current Owner Password"
              placeholderTextColor={theme.textMuted}
              value={currentPw}
              onChangeText={setCurrentPw}
              secureTextEntry
              style={[styles.input, { color: theme.text, borderColor: theme.border }]}
            />
            <TextInput
              placeholder="New Password (min 6 characters)"
              placeholderTextColor={theme.textMuted}
              value={newPw}
              onChangeText={setNewPw}
              secureTextEntry
              style={[styles.input, { color: theme.text, borderColor: theme.border }]}
            />

            <View style={{ flexDirection: 'row', gap: 10, marginTop: 8 }}>
              <AppButton
                title="Cancel"
                variant="secondary"
                onPress={() => setIsPasswordModalOpen(false)}
                style={{ flex: 1 }}
              />
              <AppButton
                title="Save"
                loading={updatePasswordMutation.isPending}
                onPress={() =>
                  updatePasswordMutation.mutate({
                    current_password: currentPw,
                    new_password: newPw,
                  })
                }
                style={{ flex: 1, backgroundColor: theme.primary }}
              />
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  headerArea: { paddingHorizontal: 16, paddingTop: 10, paddingBottom: 14, borderBottomWidth: 1 },
  headerTitle: { fontSize: 22, fontWeight: '800' },
  headerSub: { fontSize: 12, marginTop: 2 },
  scrollContent: { padding: 16, paddingBottom: 100 },
  profileCard: { flexDirection: 'row', alignItems: 'center', padding: 16, borderRadius: 18, borderWidth: 1, marginBottom: 14 },
  avatarWrap: { width: 50, height: 50, borderRadius: 16, backgroundColor: '#FF941F', alignItems: 'center', justifyContent: 'center' },
  avatarLetter: { color: '#FFFFFF', fontSize: 22, fontWeight: '800' },
  profileName: { fontSize: 17, fontWeight: '800' },
  profileDetail: { fontSize: 12, marginTop: 2 },
  sectionCard: { padding: 16, borderRadius: 18, borderWidth: 1, marginBottom: 16 },
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  sectionTitle: { fontSize: 16, fontWeight: '800' },
  botPill: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  botPillText: { fontSize: 11, fontWeight: '800' },
  botDesc: { fontSize: 13, lineHeight: 18, marginBottom: 14 },
  botActionRow: { flexDirection: 'row', gap: 10 },
  botActionBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', height: 44, borderRadius: 12, gap: 6 },
  botActionText: { color: '#FFFFFF', fontSize: 13, fontWeight: '700' },
  menuListCard: { borderRadius: 18, borderWidth: 1, overflow: 'hidden', marginBottom: 20 },
  menuRow: { flexDirection: 'row', alignItems: 'center', padding: 14, borderBottomWidth: 1 },
  menuIconWrap: { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  menuTitle: { fontSize: 14, fontWeight: '700' },
  menuSub: { fontSize: 11, marginTop: 2 },
  logoutWrap: { marginBottom: 10 },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalBackdropCenter: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', padding: 20 },
  qrModalCard: { borderTopLeftRadius: 28, borderTopRightRadius: 28, padding: 22, alignItems: 'center' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', width: '100%', alignItems: 'center', marginBottom: 12 },
  modalTitle: { fontSize: 18, fontWeight: '800' },
  qrInstructions: { fontSize: 13, textAlign: 'center', marginBottom: 16 },
  qrContainer: { width: 240, height: 240, borderRadius: 16, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
  qrSuccessText: { fontSize: 14, fontWeight: '700', marginTop: 10, textAlign: 'center' },
  pwModalCard: { padding: 20, borderRadius: 20 },
  input: { height: 46, borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, fontSize: 14, marginBottom: 12 },
});
