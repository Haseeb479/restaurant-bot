import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Dimensions,
  Image,
  ScrollView,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../../services/api/client';

const { width, height } = Dimensions.get('window');

/**
 * Premium Foodio Brand Color Palette
 */
const COLORS = {
  deepEmerald: '#064E45',   // Primary brand, login button, main titles
  warmCream: '#FFF8EF',     // Main background canvas
  foodOrange: '#FF941F',    // Accent highlight & smile underline
  sageGray: '#81958C',      // Secondary text & subtle indicators
  darkForest: '#003C35',    // Borders, contrast & button depth
};

interface PublicRestaurantInfo {
  id: number | null;
  name: string;
  is_open: boolean;
  tagline: string;
}

export default function WelcomeGatewayScreen() {
  const router = useRouter();

  // Fetch dynamic registered restaurant info from public API
  const { data } = useQuery<{ success: boolean; restaurant: PublicRestaurantInfo }>({
    queryKey: ['public-restaurant-info'],
    queryFn: () => apiClient<any>('/public/restaurant'),
    staleTime: 1000 * 60 * 5, // 5 mins cache
  });

  // Dynamic restaurant name (updates whenever a new restaurant registers)
  const restaurantName = data?.restaurant?.name || 'Fezio';

  return (
    <View style={styles.container}>
      {/* ── Native Organic Corner Waves (Zero status bar or screenshot artifacts) ── */}
      {/* Top Left Deep Emerald Organic Wave */}
      <View style={styles.topLeftWaveContainer}>
        <View style={styles.topLeftWaveOuter} />
        <View style={styles.topLeftWaveInner} />
      </View>

      {/* Top Right Warm Orange Accent Glow */}
      <View style={styles.topRightAccentGlow} />

      {/* Bottom Right Dark Forest Organic Wave */}
      <View style={styles.bottomRightWaveContainer}>
        <View style={styles.bottomRightWaveOuter} />
        <View style={styles.bottomRightWaveAccent} />
      </View>

      <SafeAreaView style={styles.safeArea}>
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
          bounces={false}
        >
          {/* ── Top Header: Brand Emblem & Registered Restaurant Name ── */}
          <View style={styles.topHeader}>
            {/* Foodio stylized emblem (fork and leaves) */}
            <Image
              source={require('../../assets/brand-symbol.png')}
              style={styles.brandEmblem}
              resizeMode="contain"
            />

            {/* Dynamic Registered Restaurant Name */}
            <Text style={styles.restaurantTitle} numberOfLines={2} adjustsFontSizeToFit>
              {restaurantName.toLowerCase()}
            </Text>

            {/* Subtitle / Tagline */}
            <View style={styles.taglineBox}>
              <Text style={styles.taglineTop}>Great Food</Text>
              <Text style={styles.taglineBottom}>Better Moments</Text>
              {/* Food Orange Smile Underline Accent */}
              <View style={styles.smileUnderline} />
            </View>
          </View>

          {/* ── Center Hero Dish (Appetizing Bowl - Completely Clean without Stray Text) ── */}
          <View style={styles.heroDishWrapper}>
            <Image
              source={require('../../assets/hero-dish-clean.png')}
              style={styles.heroDishImage}
              resizeMode="contain"
            />
          </View>

          {/* ── Primary Action Buttons ── */}
          <View style={styles.buttonSection}>
            {/* 1. Login (Owner / Staff Management) */}
            <TouchableOpacity
              activeOpacity={0.88}
              onPress={() => router.push('/(auth)/login')}
              style={styles.loginBtn}
            >
              <View style={styles.btnIconWrap}>
                <Ionicons name="person-outline" size={22} color="#FFFFFF" />
              </View>
              <View style={styles.btnDividerLight} />
              <Text style={styles.loginBtnText}>Login</Text>
              <Ionicons name="arrow-forward" size={22} color="#FFFFFF" />
            </TouchableOpacity>

            {/* 2. Dine In (Customer Table Ordering) */}
            <TouchableOpacity
              activeOpacity={0.88}
              onPress={() => router.push('/(auth)/dine-in')}
              style={styles.dineInBtn}
            >
              <View style={styles.btnIconWrap}>
                <Ionicons name="restaurant-outline" size={22} color={COLORS.deepEmerald} />
              </View>
              <View style={styles.btnDividerDark} />
              <Text style={styles.dineInBtnText}>Dine In</Text>
              <Ionicons name="arrow-forward" size={22} color={COLORS.deepEmerald} />
            </TouchableOpacity>
          </View>

          {/* ── Operational Capabilities & Powered by Foodio ── */}
          <View style={styles.footerSection}>
            <View style={styles.capabilitiesRow}>
              <View style={styles.capItem}>
                <Ionicons name="restaurant-outline" size={13} color={COLORS.sageGray} />
                <Text style={styles.capText}>POS</Text>
              </View>
              <Text style={styles.capDot}>•</Text>

              <View style={styles.capItem}>
                <Ionicons name="document-text-outline" size={13} color={COLORS.sageGray} />
                <Text style={styles.capText}>Orders</Text>
              </View>
              <Text style={styles.capDot}>•</Text>

              <View style={styles.capItem}>
                <Ionicons name="flame-outline" size={13} color={COLORS.sageGray} />
                <Text style={styles.capText}>Kitchen</Text>
              </View>
              <Text style={styles.capDot}>•</Text>

              <View style={styles.capItem}>
                <Ionicons name="bar-chart-outline" size={13} color={COLORS.sageGray} />
                <Text style={styles.capText}>Reports</Text>
              </View>
            </View>

            {/* Powered by foodio signature */}
            <View style={styles.poweredByContainer}>
              <Text style={styles.poweredByText}>
                powered by{' '}
                <Text style={styles.poweredByBrand}>foodio</Text>
              </Text>
              <View style={styles.poweredByDot} />
            </View>
          </View>
        </ScrollView>
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
    justifyContent: 'space-between',
    paddingBottom: Platform.OS === 'ios' ? 14 : 20,
  },

  /* ── Native Organic Waves (Zero unwanted status bar artifacts) ── */
  topLeftWaveContainer: {
    position: 'absolute',
    top: 0,
    left: 0,
    zIndex: 0,
  },
  topLeftWaveOuter: {
    width: 140,
    height: 125,
    backgroundColor: COLORS.deepEmerald,
    borderBottomRightRadius: 110,
    borderBottomLeftRadius: 20,
    opacity: 0.95,
  },
  topLeftWaveInner: {
    position: 'absolute',
    top: 0,
    left: 0,
    width: 110,
    height: 95,
    backgroundColor: COLORS.darkForest,
    borderBottomRightRadius: 90,
    opacity: 0.6,
  },
  topRightAccentGlow: {
    position: 'absolute',
    top: 45,
    right: -25,
    width: 110,
    height: 110,
    borderRadius: 55,
    backgroundColor: COLORS.foodOrange,
    opacity: 0.22,
    zIndex: 0,
  },
  bottomRightWaveContainer: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    zIndex: 0,
  },
  bottomRightWaveOuter: {
    width: 155,
    height: 130,
    backgroundColor: COLORS.deepEmerald,
    borderTopLeftRadius: 130,
    opacity: 0.95,
  },
  bottomRightWaveAccent: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 115,
    height: 95,
    backgroundColor: COLORS.darkForest,
    borderTopLeftRadius: 100,
    opacity: 0.8,
  },

  /* ── Top Header ── */
  topHeader: {
    alignItems: 'center',
    paddingTop: 8,
    zIndex: 1,
  },
  brandEmblem: {
    width: 68,
    height: 70,
    marginBottom: 4,
  },
  restaurantTitle: {
    fontSize: 42,
    fontWeight: '900',
    color: COLORS.deepEmerald,
    letterSpacing: -1.2,
    marginBottom: 2,
    textAlign: 'center',
    textTransform: 'lowercase',
  },
  taglineBox: {
    alignItems: 'center',
  },
  taglineTop: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.deepEmerald,
    lineHeight: 25,
  },
  taglineBottom: {
    fontSize: 18,
    fontWeight: '500',
    color: COLORS.sageGray,
    lineHeight: 23,
  },
  smileUnderline: {
    width: 65,
    height: 4.5,
    backgroundColor: COLORS.foodOrange,
    borderRadius: 3,
    marginTop: 6,
  },

  /* ── Hero Dish ── */
  heroDishWrapper: {
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 4,
    zIndex: 1,
  },
  heroDishImage: {
    width: width * 0.95,
    height: Math.min(height * 0.35, 290),
  },

  /* ── Action Buttons ── */
  buttonSection: {
    paddingHorizontal: 26,
    gap: 14,
    zIndex: 1,
    marginTop: 4,
    marginBottom: 16,
  },
  loginBtn: {
    backgroundColor: COLORS.deepEmerald,
    height: 60,
    borderRadius: 30,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 24,
    shadowColor: COLORS.darkForest,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.3,
    shadowRadius: 10,
    elevation: 5,
  },
  dineInBtn: {
    backgroundColor: '#FFFFFF',
    height: 60,
    borderRadius: 30,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 24,
    borderWidth: 2,
    borderColor: COLORS.deepEmerald,
    shadowColor: COLORS.darkForest,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 6,
    elevation: 2,
  },
  btnIconWrap: {
    width: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnDividerLight: {
    width: 1.2,
    height: 22,
    backgroundColor: 'rgba(255, 255, 255, 0.25)',
    marginHorizontal: 16,
  },
  btnDividerDark: {
    width: 1.2,
    height: 22,
    backgroundColor: 'rgba(6, 78, 69, 0.25)',
    marginHorizontal: 16,
  },
  loginBtnText: {
    flex: 1,
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: '700',
    letterSpacing: 0.2,
  },
  dineInBtnText: {
    flex: 1,
    color: COLORS.deepEmerald,
    fontSize: 18,
    fontWeight: '700',
    letterSpacing: 0.2,
  },

  /* ── Footer ── */
  footerSection: {
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 20,
    zIndex: 1,
  },
  capabilitiesRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  capItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  capText: {
    fontSize: 12,
    fontWeight: '600',
    color: COLORS.sageGray,
  },
  capDot: {
    fontSize: 12,
    color: '#B0C2BA',
  },
  poweredByContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 2,
    gap: 5,
  },
  poweredByText: {
    fontSize: 13,
    fontWeight: '500',
    color: COLORS.sageGray,
    letterSpacing: 0.3,
  },
  poweredByBrand: {
    fontWeight: '900',
    color: COLORS.deepEmerald,
  },
  poweredByDot: {
    width: 5,
    height: 5,
    borderRadius: 2.5,
    backgroundColor: COLORS.foodOrange,
  },
});
