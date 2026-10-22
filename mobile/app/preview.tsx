import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Dimensions,
  Platform,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

const { width } = Dimensions.get('window');

/**
 * Direction 1: "Nordic Ceramic & Milk Glass" Design Tokens
 */
const NORDIC = {
  canvas: '#FAF8F5',            // Warm ceramic oatmeal backdrop
  milkGlass: 'rgba(255, 255, 255, 0.88)', // Frosted milk glass surface
  milkGlassSubtle: 'rgba(255, 255, 255, 0.65)',
  border: 'rgba(228, 222, 212, 0.9)',    // Soft ceramic border stroke
  charcoal: '#1C1917',          // Fine primary typography
  stoneMuted: '#78716C',        // Secondary text
  sage: '#4A6B5D',              // Botanical sage accent
  sageLight: '#EBF1EE',         // Soft sage background wash
  sageDark: '#334D41',          // Dark contrast sage
  terracotta: '#C86D51',        // Gentle warm accent
  terracottaLight: '#FAEDE8',
  amber: '#D97706',
};

export default function NordicPreviewScreen() {
  const router = useRouter();

  // Active Screen Segment: 'sommelier' | 'zen_closing' | 'plating'
  const [activeTab, setActiveTab] = useState<'sommelier' | 'zen_closing' | 'plating'>('sommelier');

  // Interactive States
  const [selectedWineCategory, setSelectedWineCategory] = useState<'all' | 'red' | 'white' | 'botanical'>('all');
  const [wineQuantities, setWineQuantities] = useState<Record<string, number>>({});
  const [dayClosed, setDayClosed] = useState(false);
  const [activeStation, setActiveStation] = useState<'hot_line' | 'garde_manger' | 'barista'>('hot_line');

  const updateWineQty = (id: string, delta: number) => {
    setWineQuantities((prev) => {
      const cur = prev[id] || 0;
      const next = Math.max(0, cur + delta);
      return { ...prev, [id]: next };
    });
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* ── Top Nordic Pill Header ── */}
      <View style={styles.topHeader}>
        <View style={styles.headerLeft}>
          <TouchableOpacity
            onPress={() => router.back()}
            style={styles.backButton}
            activeOpacity={0.7}
          >
            <Ionicons name="arrow-back" size={18} color={NORDIC.charcoal} />
          </TouchableOpacity>
          <View>
            <Text style={styles.brandTitle}>AuraBistro • Nordic</Text>
            <Text style={styles.brandSub}>Direction 1: Ceramic & Milk Glass</Text>
          </View>
        </View>

        <View style={styles.aestheticBadge}>
          <View style={styles.sageBeaconDot} />
          <Text style={styles.aestheticBadgeText}>Milk Glass</Text>
        </View>
      </View>

      {/* ── 3-Screen Segment Switcher (Floating Milk-Glass Pill) ── */}
      <View style={styles.segmentContainer}>
        <View style={styles.segmentDock}>
          <TouchableOpacity
            onPress={() => setActiveTab('sommelier')}
            style={[styles.segmentBtn, activeTab === 'sommelier' && styles.segmentBtnActive]}
          >
            <Ionicons
              name="wine-outline"
              size={13}
              color={activeTab === 'sommelier' ? '#FFFFFF' : NORDIC.stoneMuted}
            />
            <Text style={[styles.segmentText, activeTab === 'sommelier' && styles.segmentTextActive]}>
              Cellar
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setActiveTab('zen_closing')}
            style={[styles.segmentBtn, activeTab === 'zen_closing' && styles.segmentBtnActive]}
          >
            <Ionicons
              name="pie-chart-outline"
              size={13}
              color={activeTab === 'zen_closing' ? '#FFFFFF' : NORDIC.stoneMuted}
            />
            <Text style={[styles.segmentText, activeTab === 'zen_closing' && styles.segmentTextActive]}>
              Zen Closing
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setActiveTab('plating')}
            style={[styles.segmentBtn, activeTab === 'plating' && styles.segmentBtnActive]}
          >
            <Ionicons
              name="restaurant-outline"
              size={13}
              color={activeTab === 'plating' ? '#FFFFFF' : NORDIC.stoneMuted}
            />
            <Text style={[styles.segmentText, activeTab === 'plating' && styles.segmentTextActive]}>
              Plating
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* ══════════════════════════════════════════════════════════════════
            SCREEN 1: Sommelier Cellar & Beverage Pairing
        ══════════════════════════════════════════════════════════════════ */}
        {activeTab === 'sommelier' && (
          <View>
            <View style={styles.sectionHeader}>
              <Text style={styles.sectionEyebrow}>BEVERAGE CELLAR & SOMMELIER</Text>
              <Text style={styles.sectionHeading}>Curated Pairings</Text>
              <Text style={styles.sectionSub}>Harmonized with today’s seasonal tasting menu</Text>
            </View>

            {/* Category Filter Chips */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filterStrip}>
              {[
                { id: 'all', label: 'All Cellar (14)' },
                { id: 'red', label: 'Natural Reds (6)' },
                { id: 'white', label: 'Mineral Whites (5)' },
                { id: 'botanical', label: 'Botanical Brews (3)' },
              ].map((c) => {
                const active = selectedWineCategory === c.id;
                return (
                  <TouchableOpacity
                    key={c.id}
                    onPress={() => setSelectedWineCategory(c.id as any)}
                    style={[styles.filterChip, active && styles.filterChipActive]}
                  >
                    <Text style={[styles.filterChipText, active && styles.filterChipTextActive]}>
                      {c.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>

            {/* Wine Cards */}
            <View style={styles.cardsStack}>
              {[
                {
                  id: 'w1',
                  name: 'Domaine de l’Horizon Rouge',
                  vintage: '2021 • Côtes Catalanes',
                  price: 4850,
                  temp: '16°C',
                  pairing: 'Pairs with: 45-Day Dry-Aged Ribeye',
                  notes: ['Smoky Oak', 'Wild Blackberry', 'Velvet Tannins'],
                  stock: '4 Bottles Left in Cellar',
                },
                {
                  id: 'w2',
                  name: 'Clément & Florian Berthier Sancerre',
                  vintage: '2022 • Loire Valley',
                  price: 3650,
                  temp: '10°C',
                  pairing: 'Pairs with: Pan-Seared Seabass & Fennel',
                  notes: ['Crisp Flint', 'Grapefruit Zest', 'Chalk Minerality'],
                  stock: '8 Bottles Left in Cellar',
                },
                {
                  id: 'w3',
                  name: 'Artisan Juniper & Pine Botanical Mocktail',
                  vintage: 'House Infused • Zero Proof',
                  price: 1250,
                  temp: '6°C',
                  pairing: 'Pairs with: Truffle Burrata & Heirloom Salad',
                  notes: ['Crushed Pine', 'Bergamot Tonic', 'Rosemary Smoke'],
                  stock: 'Fresh Batch Brewed Today',
                },
              ].map((wine) => {
                const qty = wineQuantities[wine.id] || 0;
                return (
                  <View key={wine.id} style={styles.wineCard}>
                    <View style={styles.cardTopRow}>
                      <View style={{ flex: 1, paddingRight: 8 }}>
                        <Text style={styles.vintageText}>{wine.vintage}</Text>
                        <Text style={styles.wineName}>{wine.name}</Text>
                      </View>
                      <View style={styles.tempBadge}>
                        <Ionicons name="thermometer-outline" size={12} color={NORDIC.sage} />
                        <Text style={styles.tempText}>{wine.temp}</Text>
                      </View>
                    </View>

                    {/* Tasting Notes Chips */}
                    <View style={styles.chipsRow}>
                      {wine.notes.map((note) => (
                        <View key={note} style={styles.tastingChip}>
                          <Text style={styles.tastingChipText}>{note}</Text>
                        </View>
                      ))}
                    </View>

                    {/* Sommelier Food Pairing Chip */}
                    <View style={styles.pairingBanner}>
                      <Ionicons name="sparkles" size={13} color={NORDIC.sage} />
                      <Text style={styles.pairingText}>{wine.pairing}</Text>
                    </View>

                    {/* Footer: Price + Stepper */}
                    <View style={styles.cardFooter}>
                      <View>
                        <Text style={styles.priceLabel}>By the bottle</Text>
                        <Text style={styles.priceAmount}>Rs. {wine.price.toLocaleString()}</Text>
                      </View>

                      {qty > 0 ? (
                        <View style={styles.stepperContainer}>
                          <TouchableOpacity
                            onPress={() => updateWineQty(wine.id, -1)}
                            style={styles.stepBtn}
                          >
                            <Ionicons name="remove" size={14} color={NORDIC.charcoal} />
                          </TouchableOpacity>
                          <Text style={styles.stepCount}>{qty}</Text>
                          <TouchableOpacity
                            onPress={() => updateWineQty(wine.id, 1)}
                            style={styles.stepBtn}
                          >
                            <Ionicons name="add" size={14} color={NORDIC.charcoal} />
                          </TouchableOpacity>
                        </View>
                      ) : (
                        <TouchableOpacity
                          onPress={() => updateWineQty(wine.id, 1)}
                          style={styles.addBottleBtn}
                          activeOpacity={0.8}
                        >
                          <Ionicons name="add" size={14} color="#FFFFFF" />
                          <Text style={styles.addBottleText}>Add to Table</Text>
                        </TouchableOpacity>
                      )}
                    </View>
                  </View>
                );
              })}
            </View>
          </View>
        )}

        {/* ══════════════════════════════════════════════════════════════════
            SCREEN 2: Zen Daily Closing & Register Reconciliation
        ══════════════════════════════════════════════════════════════════ */}
        {activeTab === 'zen_closing' && (
          <View>
            <View style={styles.sectionHeader}>
              <Text style={styles.sectionEyebrow}>END OF DAY AUDIT</Text>
              <Text style={styles.sectionHeading}>Zen Closing</Text>
              <Text style={styles.sectionSub}>Register balance and revenue reconciliation</Text>
            </View>

            {/* Central Donut Ring Summary Card */}
            <View style={styles.zenRingCard}>
              <View style={styles.ringVisualWrapper}>
                <View style={styles.ringOuter}>
                  <View style={styles.ringInner}>
                    <Text style={styles.ringGrossLabel}>TODAY'S GROSS</Text>
                    <Text style={styles.ringGrossAmount}>Rs. 184,200</Text>
                    <View style={styles.ringAuditedPill}>
                      <Ionicons name="checkmark-circle" size={12} color={NORDIC.sage} />
                      <Text style={styles.ringAuditedText}>Balanced</Text>
                    </View>
                  </View>
                </View>
              </View>

              {/* Cash vs Card Split Metrics */}
              <View style={styles.splitRow}>
                <View style={styles.splitCol}>
                  <View style={styles.splitIndicatorRow}>
                    <View style={[styles.dotCircle, { backgroundColor: NORDIC.sage }]} />
                    <Text style={styles.splitLabel}>Card / Raast QR (58%)</Text>
                  </View>
                  <Text style={styles.splitVal}>Rs. 106,836</Text>
                  <Text style={styles.splitSub}>52 Transactions</Text>
                </View>

                <View style={styles.verticalDivider} />

                <View style={styles.splitCol}>
                  <View style={styles.splitIndicatorRow}>
                    <View style={[styles.dotCircle, { backgroundColor: NORDIC.terracotta }]} />
                    <Text style={styles.splitLabel}>Cash Drawer (42%)</Text>
                  </View>
                  <Text style={styles.splitVal}>Rs. 77,364</Text>
                  <Text style={styles.splitSub}>32 Transactions</Text>
                </View>
              </View>
            </View>

            {/* Till Drawer Reconciliation Card */}
            <View style={styles.drawerCard}>
              <View style={styles.drawerHeader}>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                  <View style={styles.drawerIconBadge}>
                    <Ionicons name="wallet-outline" size={16} color={NORDIC.sage} />
                  </View>
                  <Text style={styles.drawerTitle}>Physical Cash Count</Text>
                </View>
                <Text style={styles.varianceBadge}>Variance: Rs. 0</Text>
              </View>

              <View style={styles.drawerDataRow}>
                <Text style={styles.drawerDataLabel}>Opening Cash Float:</Text>
                <Text style={styles.drawerDataVal}>Rs. 15,000</Text>
              </View>

              <View style={styles.drawerDataRow}>
                <Text style={styles.drawerDataLabel}>Cash Sales Added:</Text>
                <Text style={styles.drawerDataVal}>+ Rs. 77,364</Text>
              </View>

              <View style={styles.drawerDataRow}>
                <Text style={styles.drawerDataLabel}>Cash Paid Out (Supplies):</Text>
                <Text style={[styles.drawerDataVal, { color: NORDIC.terracotta }]}>- Rs. 4,200</Text>
              </View>

              <View style={[styles.drawerDataRow, styles.drawerTotalRow]}>
                <Text style={styles.drawerTotalLabel}>Total Cash Counted in Drawer:</Text>
                <Text style={styles.drawerTotalVal}>Rs. 88,164</Text>
              </View>
            </View>

            {/* Swipe to Seal Day Action */}
            <TouchableOpacity
              activeOpacity={0.88}
              onPress={() => {
                setDayClosed(true);
                Alert.alert(
                  'Register Sealed 🕊️',
                  'Day successfully closed. Daily closing statement dispatched to owner WhatsApp.'
                );
              }}
              style={[
                styles.sealRegisterBtn,
                dayClosed && { backgroundColor: NORDIC.sageDark },
              ]}
            >
              <Ionicons
                name={dayClosed ? 'lock-closed' : 'checkmark-done'}
                size={18}
                color="#FFFFFF"
              />
              <Text style={styles.sealRegisterText}>
                {dayClosed ? 'Register Sealed & Archived' : 'Swipe to Seal Day & Lock Register →'}
              </Text>
            </TouchableOpacity>
          </View>
        )}

        {/* ══════════════════════════════════════════════════════════════════
            SCREEN 3: Artisan Plating & Kitchen Recipe Guide
        ══════════════════════════════════════════════════════════════════ */}
        {activeTab === 'plating' && (
          <View>
            <View style={styles.sectionHeader}>
              <Text style={styles.sectionEyebrow}>KITCHEN EXPEDITION & RECIPES</Text>
              <Text style={styles.sectionHeading}>Artisan Plating</Text>
              <Text style={styles.sectionSub}>Exact culinary proportions and serving temperatures</Text>
            </View>

            {/* Station Switcher */}
            <View style={styles.stationRow}>
              {[
                { id: 'hot_line', label: 'Hot Line' },
                { id: 'garde_manger', label: 'Garde Manger' },
                { id: 'barista', label: 'Artisan Brews' },
              ].map((st) => (
                <TouchableOpacity
                  key={st.id}
                  onPress={() => setActiveStation(st.id as any)}
                  style={[styles.stationChip, activeStation === st.id && styles.stationChipActive]}
                >
                  <Text style={[styles.stationChipText, activeStation === st.id && styles.stationChipTextActive]}>
                    {st.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            {/* Plating Guide Cards */}
            <View style={styles.cardsStack}>
              {[
                {
                  id: 'p1',
                  dish: 'Truffle Butter Glazed Tenderloin',
                  course: 'Main Course • Table T4',
                  temp: '68°C Plating Temp',
                  specs: [
                    { label: 'Prime Beef Fillet', val: '220g (Medium Rare)' },
                    { label: 'Black Truffle Emulsion', val: '25ml warm drizzle' },
                    { label: 'Smoked Pomme Purée', val: '120g quenelle' },
                    { label: 'Crispy Sunchoke Chips', val: '8g garnish top' },
                  ],
                  allergens: ['Dairy (Butter)', 'Gluten Free'],
                },
                {
                  id: 'p2',
                  dish: 'Roasted Beetroot Tartare & Goat Curd',
                  course: 'Appetizer • Table T2',
                  temp: '12°C Chilled Plating',
                  specs: [
                    { label: 'Salt-Baked Golden Beets', val: '140g fine dice' },
                    { label: 'Whipped Chevre Curd', val: '35g base ring' },
                    { label: 'Pickled Mustard Seeds', val: '10g sprinkle' },
                    { label: 'Micro Amaranth Greens', val: 'Handful crown' },
                  ],
                  allergens: ['Dairy (Goat Milk)', 'Vegetarian', 'Nut Free'],
                },
              ].map((plate) => (
                <View key={plate.id} style={styles.plateCard}>
                  <View style={styles.cardTopRow}>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.vintageText}>{plate.course}</Text>
                      <Text style={styles.plateDishName}>{plate.dish}</Text>
                    </View>
                    <View style={styles.tempBadge}>
                      <Ionicons name="flame-outline" size={12} color={NORDIC.terracotta} />
                      <Text style={[styles.tempText, { color: NORDIC.terracotta }]}>{plate.temp}</Text>
                    </View>
                  </View>

                  {/* Culinary Proportions Table */}
                  <View style={styles.specsTable}>
                    {plate.specs.map((sp, idx) => (
                      <View key={idx} style={styles.specLine}>
                        <Text style={styles.specLabel}>{sp.label}</Text>
                        <Text style={styles.specVal}>{sp.val}</Text>
                      </View>
                    ))}
                  </View>

                  {/* Allergen Badges */}
                  <View style={styles.allergenRow}>
                    {plate.allergens.map((al) => (
                      <View key={al} style={styles.allergenBadge}>
                        <Text style={styles.allergenText}>{al}</Text>
                      </View>
                    ))}
                  </View>
                </View>
              ))}
            </View>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: NORDIC.canvas,
  },
  topHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 18,
    paddingTop: 8,
    paddingBottom: 10,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  backButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: NORDIC.milkGlass,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: NORDIC.border,
  },
  brandTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: NORDIC.charcoal,
    letterSpacing: -0.2,
  },
  brandSub: {
    fontSize: 10.5,
    color: NORDIC.stoneMuted,
    fontWeight: '500',
  },
  aestheticBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 14,
    backgroundColor: NORDIC.sageLight,
    borderWidth: 1,
    borderColor: 'rgba(74, 107, 93, 0.25)',
  },
  sageBeaconDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: NORDIC.sage,
  },
  aestheticBadgeText: {
    fontSize: 11,
    fontWeight: '700',
    color: NORDIC.sage,
  },
  segmentContainer: {
    paddingHorizontal: 18,
    marginBottom: 12,
  },
  segmentDock: {
    flexDirection: 'row',
    backgroundColor: NORDIC.milkGlass,
    borderRadius: 18,
    padding: 3,
    borderWidth: 1,
    borderColor: NORDIC.border,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 1,
  },
  segmentBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 5,
    paddingVertical: 8,
    borderRadius: 15,
  },
  segmentBtnActive: {
    backgroundColor: NORDIC.charcoal,
  },
  segmentText: {
    fontSize: 12,
    fontWeight: '600',
    color: NORDIC.stoneMuted,
  },
  segmentTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  scrollContent: {
    paddingHorizontal: 18,
    paddingBottom: 40,
  },
  sectionHeader: {
    marginTop: 4,
    marginBottom: 14,
  },
  sectionEyebrow: {
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 1.2,
    color: NORDIC.sage,
    marginBottom: 2,
  },
  sectionHeading: {
    fontSize: 22,
    fontWeight: '800',
    color: NORDIC.charcoal,
    letterSpacing: -0.4,
  },
  sectionSub: {
    fontSize: 12.5,
    color: NORDIC.stoneMuted,
    marginTop: 2,
  },
  filterStrip: {
    marginBottom: 14,
  },
  filterChip: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 16,
    backgroundColor: NORDIC.milkGlass,
    borderWidth: 1,
    borderColor: NORDIC.border,
    marginRight: 8,
  },
  filterChipActive: {
    backgroundColor: NORDIC.sage,
    borderColor: NORDIC.sage,
  },
  filterChipText: {
    fontSize: 11.5,
    fontWeight: '600',
    color: NORDIC.stoneMuted,
  },
  filterChipTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  cardsStack: {
    gap: 14,
  },
  wineCard: {
    backgroundColor: NORDIC.milkGlass,
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    borderColor: NORDIC.border,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 1,
  },
  cardTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  vintageText: {
    fontSize: 11,
    fontWeight: '600',
    color: NORDIC.stoneMuted,
  },
  wineName: {
    fontSize: 15.5,
    fontWeight: '800',
    color: NORDIC.charcoal,
    marginTop: 2,
  },
  tempBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    backgroundColor: NORDIC.sageLight,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 10,
  },
  tempText: {
    fontSize: 11,
    fontWeight: '700',
    color: NORDIC.sage,
  },
  chipsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginVertical: 8,
  },
  tastingChip: {
    backgroundColor: NORDIC.canvas,
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: NORDIC.border,
  },
  tastingChipText: {
    fontSize: 10.5,
    fontWeight: '600',
    color: NORDIC.charcoal,
  },
  pairingBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: NORDIC.sageLight,
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 10,
    marginVertical: 6,
  },
  pairingText: {
    fontSize: 11.5,
    fontWeight: '600',
    color: NORDIC.sageDark,
  },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 10,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: 'rgba(228, 222, 212, 0.6)',
  },
  priceLabel: {
    fontSize: 10,
    color: NORDIC.stoneMuted,
    fontWeight: '500',
  },
  priceAmount: {
    fontSize: 16,
    fontWeight: '800',
    color: NORDIC.charcoal,
  },
  addBottleBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: NORDIC.sage,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 14,
  },
  addBottleText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '700',
  },
  stepperContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: NORDIC.canvas,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: NORDIC.border,
    paddingHorizontal: 4,
    height: 32,
  },
  stepBtn: {
    width: 26,
    height: 26,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepCount: {
    fontSize: 13,
    fontWeight: '800',
    color: NORDIC.charcoal,
    paddingHorizontal: 8,
  },

  /* ── Zen Closing Styles ── */
  zenRingCard: {
    backgroundColor: NORDIC.milkGlass,
    borderRadius: 24,
    padding: 20,
    borderWidth: 1,
    borderColor: NORDIC.border,
    alignItems: 'center',
    marginBottom: 14,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 1,
  },
  ringVisualWrapper: {
    marginVertical: 10,
  },
  ringOuter: {
    width: 170,
    height: 170,
    borderRadius: 85,
    borderWidth: 8,
    borderColor: NORDIC.sageLight,
    borderTopColor: NORDIC.sage,
    borderRightColor: NORDIC.terracotta,
    alignItems: 'center',
    justifyContent: 'center',
  },
  ringInner: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  ringGrossLabel: {
    fontSize: 9.5,
    fontWeight: '800',
    letterSpacing: 1,
    color: NORDIC.stoneMuted,
  },
  ringGrossAmount: {
    fontSize: 20,
    fontWeight: '900',
    color: NORDIC.charcoal,
    marginTop: 2,
    marginBottom: 4,
  },
  ringAuditedPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: NORDIC.sageLight,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
  },
  ringAuditedText: {
    fontSize: 10,
    fontWeight: '700',
    color: NORDIC.sage,
  },
  splitRow: {
    flexDirection: 'row',
    width: '100%',
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: 'rgba(228, 222, 212, 0.7)',
  },
  splitCol: {
    flex: 1,
  },
  splitIndicatorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 2,
  },
  dotCircle: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  splitLabel: {
    fontSize: 11,
    color: NORDIC.stoneMuted,
    fontWeight: '600',
  },
  splitVal: {
    fontSize: 15,
    fontWeight: '800',
    color: NORDIC.charcoal,
  },
  splitSub: {
    fontSize: 10,
    color: NORDIC.stoneMuted,
    marginTop: 1,
  },
  verticalDivider: {
    width: 1,
    backgroundColor: NORDIC.border,
    marginHorizontal: 12,
  },
  drawerCard: {
    backgroundColor: NORDIC.milkGlass,
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: NORDIC.border,
    marginBottom: 16,
  },
  drawerHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  drawerIconBadge: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: NORDIC.sageLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  drawerTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: NORDIC.charcoal,
  },
  varianceBadge: {
    fontSize: 11,
    fontWeight: '700',
    color: NORDIC.sage,
    backgroundColor: NORDIC.sageLight,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
  },
  drawerDataRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 5,
  },
  drawerDataLabel: {
    fontSize: 12,
    color: NORDIC.stoneMuted,
  },
  drawerDataVal: {
    fontSize: 12.5,
    fontWeight: '700',
    color: NORDIC.charcoal,
  },
  drawerTotalRow: {
    borderTopWidth: 1,
    borderTopColor: NORDIC.border,
    paddingTop: 8,
    marginTop: 4,
  },
  drawerTotalLabel: {
    fontSize: 12.5,
    fontWeight: '700',
    color: NORDIC.charcoal,
  },
  drawerTotalVal: {
    fontSize: 14,
    fontWeight: '800',
    color: NORDIC.sageDark,
  },
  sealRegisterBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: NORDIC.sage,
    paddingVertical: 14,
    borderRadius: 18,
    shadowColor: NORDIC.sageDark,
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.25,
    shadowRadius: 6,
    elevation: 3,
  },
  sealRegisterText: {
    color: '#FFFFFF',
    fontSize: 13.5,
    fontWeight: '700',
    letterSpacing: 0.2,
  },

  /* ── Plating Styles ── */
  stationRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 14,
  },
  stationChip: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 14,
    backgroundColor: NORDIC.milkGlass,
    borderWidth: 1,
    borderColor: NORDIC.border,
    alignItems: 'center',
  },
  stationChipActive: {
    backgroundColor: NORDIC.charcoal,
  },
  stationChipText: {
    fontSize: 11.5,
    fontWeight: '600',
    color: NORDIC.stoneMuted,
  },
  stationChipTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  plateCard: {
    backgroundColor: NORDIC.milkGlass,
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    borderColor: NORDIC.border,
  },
  plateDishName: {
    fontSize: 15,
    fontWeight: '800',
    color: NORDIC.charcoal,
    marginTop: 2,
  },
  specsTable: {
    backgroundColor: NORDIC.canvas,
    borderRadius: 12,
    padding: 10,
    marginVertical: 10,
    gap: 6,
  },
  specLine: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  specLabel: {
    fontSize: 11.5,
    color: NORDIC.stoneMuted,
  },
  specVal: {
    fontSize: 11.5,
    fontWeight: '700',
    color: NORDIC.charcoal,
  },
  allergenRow: {
    flexDirection: 'row',
    gap: 6,
  },
  allergenBadge: {
    backgroundColor: NORDIC.sageLight,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
  },
  allergenText: {
    fontSize: 10,
    fontWeight: '700',
    color: NORDIC.sageDark,
  },
});
