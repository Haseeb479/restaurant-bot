import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import { useAppTheme } from '../../hooks/useAppTheme';
import { AppButton } from '../../components/AppButton';
import { AppInput } from '../../components/AppInput';
import { useAuthStore } from '../../stores/authStore';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

export default function LoginScreen() {
  const { theme } = useAppTheme();
  const router = useRouter();
  const login = useAuthStore((s) => s.login);

  const [restaurantName, setRestaurantName] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleLogin = async () => {
    if (!restaurantName.trim()) {
      setError('Please enter your restaurant identifier or phone.');
      return;
    }
    if (!password) {
      setError('Please enter your password.');
      return;
    }
    setError(null);
    setLoading(true);
    try {
      await login(restaurantName.trim(), password);
      router.replace('/(app)');
    } catch (e: any) {
      setError(e.message || 'Failed to sign in. Please check credentials.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={[styles.container, { backgroundColor: theme.background }]}
    >
      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Back to Gateway Screen */}
        <TouchableOpacity
          onPress={() => router.replace('/(auth)')}
          style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 12, gap: 6 }}
        >
          <Ionicons name="arrow-back" size={20} color={theme.text} />
          <Text style={{ fontSize: 13, fontWeight: '700', color: theme.text }}>Back</Text>
        </TouchableOpacity>

        {/* Brand Logo & Title */}
        <View style={styles.header}>
          <View style={[styles.logoIconWrap, { backgroundColor: theme.primary }]}>
            <Ionicons name="restaurant" size={36} color="#FFFFFF" />
          </View>
          <Text style={[styles.brandTitle, { color: theme.text }]}>foodio POS</Text>
          <Text style={[styles.brandSubtitle, { color: theme.textMuted }]}>
            Sign in to access your order terminal & operations
          </Text>
        </View>

        {/* Login Form Card */}
        <View style={[styles.card, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <AppInput
            label="Restaurant Identifier / WhatsApp"
            placeholder="e.g. 923293647476 or grillcafe"
            value={restaurantName}
            onChangeText={(t) => {
              setRestaurantName(t);
              setError(null);
            }}
            autoCapitalize="none"
          />

          <AppInput
            label="Manager Password"
            placeholder="Enter your secret password"
            value={password}
            onChangeText={(t) => {
              setPassword(t);
              setError(null);
            }}
            secureTextEntry
          />

          {error ? (
            <View style={styles.errorBox}>
              <Ionicons name="alert-circle" size={16} color={theme.danger} />
              <Text style={[styles.errorMessage, { color: theme.danger }]}>{error}</Text>
            </View>
          ) : null}

          <AppButton
            title="Sign In to POS"
            loading={loading}
            onPress={handleLogin}
            style={styles.loginBtn}
          />
        </View>

        <View style={styles.footer}>
          <Text style={[styles.footerText, { color: theme.textMuted }]}>
            Connected to Foodio Multi-Tenant Cloud
          </Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  header: {
    alignItems: 'center',
    marginBottom: 28,
  },
  logoIconWrap: {
    width: 68,
    height: 68,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
    elevation: 4,
    shadowColor: '#064E45',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
  },
  brandTitle: {
    fontSize: 26,
    fontWeight: '800',
    letterSpacing: -0.5,
  },
  brandSubtitle: {
    fontSize: 13,
    marginTop: 6,
    textAlign: 'center',
    maxWidth: 280,
  },
  card: {
    padding: 22,
    borderRadius: 20,
    borderWidth: 1,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 14,
  },
  errorMessage: {
    fontSize: 13,
    flex: 1,
  },
  loginBtn: {
    marginTop: 8,
  },
  footer: {
    alignItems: 'center',
    marginTop: 24,
  },
  footerText: {
    fontSize: 12,
  },
});
