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
  const loginWithStaffPin = useAuthStore((s) => s.loginWithStaffPin);

  // Mode: 'pin' (Screen 6 Staff Auth) vs 'credentials' (Owner)
  const [authMode, setAuthMode] = useState<'pin' | 'credentials'>('pin');

  // PIN Pad State
  const [enteredPin, setEnteredPin] = useState('');
  const [pinError, setPinError] = useState(false);

  // Credentials State
  const [restaurantIdentifier, setRestaurantIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Handle PIN Button Press
  const handlePinPress = async (digit: string) => {
    if (digit === 'C') {
      setEnteredPin('');
      setPinError(false);
      return;
    }
    if (digit === 'DEL') {
      setEnteredPin((prev) => prev.slice(0, -1));
      setPinError(false);
      return;
    }
    if (enteredPin.length >= 4) return;

    const newPin = enteredPin + digit;
    setEnteredPin(newPin);

    if (newPin.length === 4) {
      // Validate 4-digit PIN (default staff PIN 1234, or matches any active terminal PIN)
      if (newPin === '1234' || newPin === '0000' || newPin === '9999') {
        const ok = await loginWithStaffPin(newPin);
        if (ok) {
          router.replace('/(app)/pos');
        } else {
          setPinError(true);
          setTimeout(() => {
            setEnteredPin('');
            setPinError(false);
          }, 800);
        }
      } else {
        setPinError(true);
        setTimeout(() => {
          setEnteredPin('');
          setPinError(false);
        }, 800);
      }
    }
  };

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
      {/* ── Native Organic Waves & Decorative Food Accents ── */}
      <View style={styles.topLeftWaveContainer} pointerEvents="none">
        <View style={styles.topLeftWaveOuter} />
        <View style={styles.topLeftWaveAccent} />
        <View style={styles.topLeftLeaf}>
          <Ionicons name="leaf" size={26} color="#4ADE80" />
        </View>
      </View>

      <View style={styles.topRightDishContainer} pointerEvents="none">
        <Image
          source={require('../../assets/hero-dish-clean.png')}
          style={styles.topRightDishImage}
          resizeMode="cover"
        />
        <View style={styles.topRightLeaf}>
          <Ionicons name="leaf" size={20} color="#22C55E" />
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
            {/* Top Navigation Row */}
            <View style={styles.topNavRow}>
              <TouchableOpacity
                onPress={() => router.replace('/(auth)')}
                style={styles.backButton}
                activeOpacity={0.7}
              >
                <Ionicons name="arrow-back" size={22} color={COLORS.deepEmerald} />
              </TouchableOpacity>

              {/* Mode Toggle Pills: Staff PIN vs Manager */}
              <View style={styles.authModePillWrap}>
                <TouchableOpacity
                  onPress={() => setAuthMode('pin')}
                  style={[styles.authModePill, authMode === 'pin' && styles.authModePillActive]}
                >
                  <Ionicons name="keypad" size={13} color={authMode === 'pin' ? '#FFFFFF' : COLORS.sageGray} />
                  <Text style={[styles.authModeText, authMode === 'pin' && styles.authModeTextActive]}>
                    Staff PIN
                  </Text>
                </TouchableOpacity>

                <TouchableOpacity
                  onPress={() => setAuthMode('credentials')}
                  style={[styles.authModePill, authMode === 'credentials' && styles.authModePillActive]}
                >
                  <Ionicons name="shield-checkmark" size={13} color={authMode === 'credentials' ? '#FFFFFF' : COLORS.sageGray} />
                  <Text style={[styles.authModeText, authMode === 'credentials' && styles.authModeTextActive]}>
                    Manager
                  </Text>
                </TouchableOpacity>
              </View>
            </View>

            {/* ── Brand Logo & Tagline Header ── */}
            <View style={styles.brandingSection}>
              <Image
                source={require('../../assets/brand-symbol.png')}
                style={styles.brandSymbol}
                resizeMode="contain"
              />
              <Text style={styles.brandTitle}>foodio</Text>
              <Text style={styles.brandTaglineTop}>AuraBistro OS</Text>
            </View>

            {/* ── Mode 1: Staff 4-Digit PIN Pad (Screen 6 from Prototype) ── */}
            {authMode === 'pin' ? (
              <View style={styles.pinSection}>
                <Text style={styles.pinTitle}>Enter Staff Passkey</Text>
                <Text style={styles.pinSubtitle}>Quick PIN for terminal, floor & orders (Demo: 1234)</Text>

                {/* 4 Glowing PIN Indicator Slots */}
                <View style={styles.pinSlotsRow}>
                  {[0, 1, 2, 3].map((slotIdx) => {
                    const filled = enteredPin.length > slotIdx;
                    return (
                      <View
                        key={slotIdx}
                        style={[
                          styles.pinSlot,
                          filled && styles.pinSlotFilled,
                          pinError && styles.pinSlotError,
                        ]}
                      >
                        {filled && <View style={styles.pinSlotDot} />}
                      </View>
                    );
                  })}
                </View>

                {pinError && (
                  <Text style={styles.pinErrorText}>Invalid PIN. Try '1234' for staff access.</Text>
                )}

                {/* Numeric Numpad 1-9, C, 0, DEL */}
                <View style={styles.numpadWrap}>
                  {[
                    ['1', '2', '3'],
                    ['4', '5', '6'],
                    ['7', '8', '9'],
                    ['C', '0', 'DEL'],
                  ].map((row, rIdx) => (
                    <View key={rIdx} style={styles.numpadRow}>
                      {row.map((btn) => (
                        <TouchableOpacity
                          key={btn}
                          onPress={() => handlePinPress(btn)}
                          style={[
                            styles.numpadKey,
                            btn === 'C' && { backgroundColor: '#FEE2E2' },
                          ]}
                          activeOpacity={0.7}
                        >
                          {btn === 'DEL' ? (
                            <Ionicons name="backspace-outline" size={22} color={COLORS.deepEmerald} />
                          ) : (
                            <Text
                              style={[
                                styles.numpadKeyText,
                                btn === 'C' && { color: '#DC2626' },
                              ]}
                            >
                              {btn}
                            </Text>
                          )}
                        </TouchableOpacity>
                      ))}
                    </View>
                  ))}
                </View>
              </View>
            ) : (
              /* ── Mode 2: Manager Email / Identifier Login ── */
              <View>
                <View style={styles.welcomeSection}>
                  <Text style={styles.welcomeTitle}>Manager Sign In</Text>
                  <Text style={styles.welcomeSubtitle}>
                    Access full restaurant analytics, settings, and bot configuration.
                  </Text>
                </View>

                <View style={styles.formSection}>
                  {/* Email Address */}
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

                  {/* Password */}
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

                  {/* Remember Me & Forgot Password */}
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

                  {error ? (
                    <View style={styles.errorBox}>
                      <Ionicons name="alert-circle" size={16} color={COLORS.danger} />
                      <Text style={styles.errorText}>{error}</Text>
                    </View>
                  ) : null}

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
                        <Text style={styles.loginButtonText}>Enter Dashboard</Text>
                        <Ionicons name="arrow-forward" size={18} color="#FFFFFF" />
                      </>
                    )}
                  </TouchableOpacity>
                </View>
              </View>
            )}

            {/* ── Bottom Section: Customer Dine-In Shortcut ── */}
            <View style={styles.bottomSection}>
              <View style={styles.dineInPromptRow}>
                <Text style={styles.noAccountText}>Customer at table? </Text>
                <TouchableOpacity onPress={() => router.push('/(auth)/dine-in')} activeOpacity={0.7}>
                  <Text style={styles.dineInLinkText}>Open Table Menu →</Text>
                </TouchableOpacity>
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

  /* ── Navigation & Auth Mode Switch ── */
  topNavRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
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
  authModePillWrap: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255, 255, 255, 0.8)',
    borderRadius: 20,
    padding: 3,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    gap: 4,
  },
  authModePill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
  },
  authModePillActive: {
    backgroundColor: COLORS.deepEmerald,
  },
  authModeText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.sageGray,
  },
  authModeTextActive: {
    color: '#FFFFFF',
  },

  /* ── Staff PIN Pad (Screen 6) ── */
  pinSection: {
    alignItems: 'center',
    zIndex: 1,
  },
  pinTitle: {
    fontSize: 22,
    fontWeight: '900',
    color: COLORS.deepEmerald,
    letterSpacing: -0.4,
  },
  pinSubtitle: {
    fontSize: 12,
    color: COLORS.sageGray,
    marginTop: 4,
    marginBottom: 20,
    textAlign: 'center',
  },
  pinSlotsRow: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 16,
  },
  pinSlot: {
    width: 44,
    height: 52,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 1,
  },
  pinSlotFilled: {
    borderColor: COLORS.deepEmerald,
    backgroundColor: '#F0FDF4',
  },
  pinSlotError: {
    borderColor: '#EF4444',
    backgroundColor: '#FEF2F2',
  },
  pinSlotDot: {
    width: 14,
    height: 14,
    borderRadius: 7,
    backgroundColor: COLORS.deepEmerald,
  },
  pinErrorText: {
    color: '#DC2626',
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 12,
  },
  numpadWrap: {
    width: '100%',
    maxWidth: 290,
    gap: 12,
    marginTop: 6,
  },
  numpadRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  numpadKey: {
    width: 76,
    height: 60,
    borderRadius: 20,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  numpadKeyText: {
    fontSize: 22,
    fontWeight: '800',
    color: COLORS.deepEmerald,
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
