import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  Image,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Dimensions,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../../stores/authStore';

const { width } = Dimensions.get('window');

/**
 * Signature Foodio Color Palette
 */
const COLORS = {
  deepEmerald: '#064E45',   // Primary brand, login button, main titles
  warmCream: '#FFF8EF',     // Background canvas
  foodOrange: '#FF941F',    // Accent highlight & links
  sageGray: '#81958C',      // Secondary text & subtle indicators
  darkForest: '#003C35',    // Borders, contrast & button depth
  inputBg: '#FFFFFF',
  inputBorder: '#E2E8F0',
  textDark: '#0A3B34',
  textMuted: '#64748B',
  danger: '#DC2626',
};

export default function LoginScreen() {
  const router = useRouter();
  const login = useAuthStore((s) => s.login);

  const [restaurantIdentifier, setRestaurantIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleLogin = async () => {
    if (!restaurantIdentifier.trim()) {
      setError('Please enter your email or restaurant identifier.');
      return;
    }
    if (!password) {
      setError('Please enter your password.');
      return;
    }

    setError(null);
    setLoading(true);

    try {
      await login(restaurantIdentifier.trim(), password);
      router.replace('/(app)');
    } catch (e: any) {
      setError(e.message || 'Wrong restaurant identifier or password. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = () => {
    Alert.alert(
      'Forgot Password',
      'Please use your registered WhatsApp number or visit the Foodio web dashboard to reset your owner credentials.',
      [{ text: 'OK' }]
    );
  };

  return (
    <View style={styles.container}>
      {/* ── Native Organic Waves & Decorative Food Accents (Clean, zero unwanted screenshot artifacts) ── */}

      {/* Top Left Organic Emerald Wave with Orange Accent Curve */}
      <View style={styles.topLeftWaveContainer} pointerEvents="none">
        <View style={styles.topLeftWaveOuter} />
        <View style={styles.topLeftWaveAccent} />
        {/* Decorative Floating Leaf */}
        <View style={styles.topLeftLeaf}>
          <Ionicons name="leaf" size={26} color="#4ADE80" />
        </View>
      </View>

      {/* Top Right Floating Food Dish Plate */}
      <View style={styles.topRightDishContainer} pointerEvents="none">
        <Image
          source={require('../../assets/hero-dish-clean.png')}
          style={styles.topRightDishImage}
          resizeMode="cover"
        />
        {/* Floating leaf near dish */}
        <View style={styles.topRightLeaf}>
          <Ionicons name="leaf" size={20} color="#22C55E" />
        </View>
      </View>

      {/* Bottom Right Organic Emerald Wave */}
      <View style={styles.bottomRightWaveContainer} pointerEvents="none">
        <View style={styles.bottomRightWaveOuter} />
        <View style={styles.bottomRightWaveAccent} />
        {/* Bottom floating leaf */}
        <View style={styles.bottomLeaf}>
          <Ionicons name="leaf" size={24} color="#22C55E" />
        </View>
      </View>

      <SafeAreaView style={styles.safeArea} edges={['top', 'bottom']}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={{ flex: 1 }}
        >
          <ScrollView
            contentContainerStyle={styles.scrollContent}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
          >
            {/* Top Navigation Row: Back Button */}
            <View style={styles.topNavRow}>
              <TouchableOpacity
                onPress={() => router.replace('/(auth)')}
                style={styles.backButton}
                activeOpacity={0.7}
              >
                <Ionicons name="arrow-back" size={22} color={COLORS.deepEmerald} />
              </TouchableOpacity>
            </View>

            {/* ── Brand Logo & Tagline Header ── */}
            <View style={styles.brandingSection}>
              {/* Foodio Stylized Emblem */}
              <Image
                source={require('../../assets/brand-symbol.png')}
                style={styles.brandSymbol}
                resizeMode="contain"
              />

              {/* Foodio Wordmark */}
              <Text style={styles.brandTitle}>foodio</Text>

              {/* Tagline */}
              <Text style={styles.brandTaglineTop}>Great Food</Text>
              <Text style={styles.brandTaglineBottom}>Better Moments</Text>
            </View>

            {/* ── Welcome Heading ── */}
            <View style={styles.welcomeSection}>
              <Text style={styles.welcomeTitle}>Welcome Back!</Text>
              <Text style={styles.welcomeSubtitle}>
                Log in to your account and continue your food journey.
              </Text>
            </View>

            {/* ── Login Form Fields ── */}
            <View style={styles.formSection}>
              {/* Input 1: Email Address / Restaurant Identifier */}
              <View style={[styles.inputPill, error ? { borderColor: COLORS.danger } : null]}>
                <Ionicons name="mail-outline" size={20} color={COLORS.sageGray} style={styles.inputIcon} />
                <TextInput
                  style={styles.textInput}
                  placeholder="Email Address"
                  placeholderTextColor="#94A3B8"
                  value={restaurantIdentifier}
                  onChangeText={(text) => {
                    setRestaurantIdentifier(text);
                    setError(null);
                  }}
                  autoCapitalize="none"
                  autoCorrect={false}
                  keyboardType="email-address"
                />
              </View>

              {/* Input 2: Password with Show/Hide Toggle */}
              <View style={[styles.inputPill, error ? { borderColor: COLORS.danger } : null]}>
                <Ionicons name="lock-closed-outline" size={20} color={COLORS.sageGray} style={styles.inputIcon} />
                <TextInput
                  style={styles.textInput}
                  placeholder="Password"
                  placeholderTextColor="#94A3B8"
                  value={password}
                  onChangeText={(text) => {
                    setPassword(text);
                    setError(null);
                  }}
                  secureTextEntry={!showPassword}
                  autoCapitalize="none"
                  autoCorrect={false}
                />
                <TouchableOpacity
                  onPress={() => setShowPassword(!showPassword)}
                  style={styles.eyeButton}
                  activeOpacity={0.7}
                >
                  <Ionicons
                    name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                    size={20}
                    color={COLORS.sageGray}
                  />
                </TouchableOpacity>
              </View>

              {/* Options Row: Remember Me & Forgot Password */}
              <View style={styles.optionsRow}>
                <TouchableOpacity
                  style={styles.rememberMeRow}
                  onPress={() => setRememberMe(!rememberMe)}
                  activeOpacity={0.8}
                >
                  <View style={[styles.checkbox, rememberMe && styles.checkboxActive]}>
                    {rememberMe && <Ionicons name="checkmark" size={13} color="#FFFFFF" />}
                  </View>
                  <Text style={styles.rememberMeText}>Remember me</Text>
                </TouchableOpacity>

                <TouchableOpacity onPress={handleForgotPassword} activeOpacity={0.7}>
                  <Text style={styles.forgotPasswordText}>Forgot Password?</Text>
                </TouchableOpacity>
              </View>

              {/* Error Banner */}
              {error ? (
                <View style={styles.errorBox}>
                  <Ionicons name="alert-circle" size={16} color={COLORS.danger} />
                  <Text style={styles.errorText}>{error}</Text>
                </View>
              ) : null}

              {/* ── Login Capsule Button ── */}
              <TouchableOpacity
                onPress={handleLogin}
                disabled={loading}
                style={styles.loginButton}
                activeOpacity={0.85}
              >
                {loading ? (
                  <ActivityIndicator color="#FFFFFF" size="small" />
                ) : (
                  <>
                    <Text style={styles.loginButtonText}>Login</Text>
                    <Ionicons name="arrow-forward" size={18} color="#FFFFFF" />
                  </>
                )}
              </TouchableOpacity>
            </View>

            {/* ── Bottom Section: Dine In Link & Brand Stamp ── */}
            <View style={styles.bottomSection}>
              {/* Don't have an account? Dine In -> */}
              <View style={styles.dineInPromptRow}>
                <Text style={styles.noAccountText}>Don't have an account? </Text>
                <TouchableOpacity onPress={() => router.push('/(auth)/dine-in')} activeOpacity={0.7}>
                  <Text style={styles.dineInLinkText}>Dine In →</Text>
                </TouchableOpacity>
              </View>

              {/* Bottom Left Artistic Stamp: Good Food Always */}
              <View style={styles.goodFoodStamp}>
                <Text style={styles.stampLine1}>Good</Text>
                <Text style={styles.stampLine2}>Food</Text>
                <Text style={styles.stampLine3}>Always</Text>
              </View>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.warmCream,
  },
  safeArea: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 26,
    paddingBottom: 24,
    justifyContent: 'space-between',
  },

  /* ── Native Organic Corners (Zero mockup status bar/time) ── */
  topLeftWaveContainer: {
    position: 'absolute',
    top: 0,
    left: 0,
    width: 140,
    height: 140,
    zIndex: 0,
  },
  topLeftWaveOuter: {
    width: 125,
    height: 110,
    backgroundColor: COLORS.deepEmerald,
    borderBottomRightRadius: 100,
    opacity: 0.95,
  },
  topLeftWaveAccent: {
    position: 'absolute',
    top: 0,
    left: 0,
    width: 140,
    height: 125,
    borderBottomRightRadius: 115,
    borderWidth: 3,
    borderColor: COLORS.foodOrange,
    borderTopWidth: 0,
    borderLeftWidth: 0,
    opacity: 0.85,
  },
  topLeftLeaf: {
    position: 'absolute',
    top: 75,
    left: 55,
    transform: [{ rotate: '-25deg' }],
    opacity: 0.85,
  },

  topRightDishContainer: {
    position: 'absolute',
    top: -25,
    right: -35,
    width: 175,
    height: 175,
    borderRadius: 90,
    overflow: 'hidden',
    zIndex: 0,
    shadowColor: COLORS.darkForest,
    shadowOffset: { width: -2, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
    elevation: 3,
  },
  topRightDishImage: {
    width: '100%',
    height: '100%',
    borderRadius: 90,
  },
  topRightLeaf: {
    position: 'absolute',
    bottom: 15,
    left: 10,
    transform: [{ rotate: '40deg' }],
  },

  bottomRightWaveContainer: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 150,
    height: 130,
    zIndex: 0,
  },
  bottomRightWaveOuter: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 135,
    height: 115,
    backgroundColor: COLORS.deepEmerald,
    borderTopLeftRadius: 120,
    opacity: 0.95,
  },
  bottomRightWaveAccent: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 150,
    height: 128,
    borderTopLeftRadius: 130,
    borderWidth: 3,
    borderColor: COLORS.foodOrange,
    borderBottomWidth: 0,
    borderRightWidth: 0,
    opacity: 0.85,
  },
  bottomLeaf: {
    position: 'absolute',
    bottom: 75,
    right: 65,
    transform: [{ rotate: '55deg' }],
  },

  /* ── Navigation ── */
  topNavRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 4,
    marginBottom: 8,
    zIndex: 2,
  },
  backButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: 'rgba(255, 255, 255, 0.75)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },

  /* ── Branding ── */
  brandingSection: {
    alignItems: 'center',
    marginBottom: 20,
    zIndex: 1,
  },
  brandSymbol: {
    width: 64,
    height: 64,
    marginBottom: 2,
  },
  brandTitle: {
    fontSize: 40,
    fontWeight: '900',
    color: COLORS.deepEmerald,
    letterSpacing: -1,
    textTransform: 'lowercase',
  },
  brandTaglineTop: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textDark,
    marginTop: 2,
  },
  brandTaglineBottom: {
    fontSize: 14,
    fontWeight: '500',
    color: COLORS.sageGray,
  },

  /* ── Welcome Heading ── */
  welcomeSection: {
    marginBottom: 22,
    zIndex: 1,
  },
  welcomeTitle: {
    fontSize: 28,
    fontWeight: '900',
    color: COLORS.deepEmerald,
    letterSpacing: -0.5,
  },
  welcomeSubtitle: {
    fontSize: 13.5,
    color: COLORS.sageGray,
    marginTop: 5,
    lineHeight: 19,
    maxWidth: 290,
  },

  /* ── Form Section ── */
  formSection: {
    gap: 14,
    zIndex: 1,
  },
  inputPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.inputBg,
    borderRadius: 24,
    borderWidth: 1.2,
    borderColor: COLORS.inputBorder,
    height: 52,
    paddingHorizontal: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  inputIcon: {
    marginRight: 10,
  },
  textInput: {
    flex: 1,
    fontSize: 14,
    color: COLORS.textDark,
    fontWeight: '500',
  },
  eyeButton: {
    padding: 6,
  },

  /* ── Options Row ── */
  optionsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 4,
    marginTop: 2,
    marginBottom: 4,
  },
  rememberMeRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  checkbox: {
    width: 18,
    height: 18,
    borderRadius: 5,
    borderWidth: 1.5,
    borderColor: COLORS.deepEmerald,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'transparent',
  },
  checkboxActive: {
    backgroundColor: COLORS.deepEmerald,
  },
  rememberMeText: {
    fontSize: 13,
    color: COLORS.textMuted,
    fontWeight: '600',
    marginLeft: 8,
  },
  forgotPasswordText: {
    fontSize: 13,
    color: COLORS.foodOrange,
    fontWeight: '700',
  },

  /* ── Error Banner ── */
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FCA5A5',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 8,
    gap: 8,
  },
  errorText: {
    flex: 1,
    fontSize: 12,
    color: COLORS.danger,
    fontWeight: '600',
  },

  /* ── Login Pill Button ── */
  loginButton: {
    backgroundColor: COLORS.deepEmerald,
    height: 52,
    borderRadius: 26,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    marginTop: 6,
    shadowColor: COLORS.darkForest,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '800',
    letterSpacing: 0.2,
  },

  /* ── Bottom Section ── */
  bottomSection: {
    marginTop: 28,
    alignItems: 'center',
    zIndex: 1,
  },
  dineInPromptRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  noAccountText: {
    fontSize: 13.5,
    color: COLORS.sageGray,
    fontWeight: '500',
  },
  dineInLinkText: {
    fontSize: 13.5,
    color: COLORS.foodOrange,
    fontWeight: '800',
  },

  /* ── Good Food Always Hand-Lettered Badge ── */
  goodFoodStamp: {
    alignSelf: 'flex-start',
    marginTop: 6,
    transform: [{ rotate: '-8deg' }],
  },
  stampLine1: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.deepEmerald,
    fontStyle: 'italic',
    lineHeight: 15,
  },
  stampLine2: {
    fontSize: 17,
    fontWeight: '900',
    color: COLORS.deepEmerald,
    fontStyle: 'italic',
    lineHeight: 18,
  },
  stampLine3: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.deepEmerald,
    fontStyle: 'italic',
    lineHeight: 16,
  },
});
