import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Modal,
  Alert,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';

interface TrayItem {
  item_id: number;
  name: string;
  variant_id?: number;
  variant_name?: string;
  price: number;
  quantity: number;
}

export default function CustomerDineInScreen() {
  const router = useRouter();

  // Selected Table State
  const [selectedTable, setSelectedTable] = useState('4');
  const [customTableInput, setCustomTableInput] = useState('');
  const [isCustomTableOpen, setIsCustomTableOpen] = useState(false);

  // Category filter & Search
  const [selectedCategory, setSelectedCategory] = useState<number | 'all'>('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Customer Tray (Cart)
  const [tray, setTray] = useState<TrayItem[]>([]);
  const [isTrayModalOpen, setIsTrayModalOpen] = useState(false);

  // Variant modal
  const [variantItem, setVariantItem] = useState<any | null>(null);

  // Customer details for table
  const [guestName, setGuestName] = useState('');
  const [tableNotes, setTableNotes] = useState('');

  // Success modal
  const [confirmedOrder, setConfirmedOrder] = useState<any | null>(null);

  // Fetch Public Dine-In Menu
  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['public-dine-in-menu'],
    queryFn: () => apiClient<any>('/dine-in/menu'),
  });

  // Submit Dine-In Order Mutation
  const placeDineInMutation = useMutation({
    mutationFn: (payload: any) =>
      apiClient('/dine-in/orders', {
        method: 'POST',
        body: JSON.stringify(payload),
      }),
    onSuccess: (res: any) => {
      setIsTrayModalOpen(false);
      setConfirmedOrder(res?.order);
      setTray([]);
      setTableNotes('');
      setGuestName('');
    },
    onError: (err: any) => {
      Alert.alert('Order Failed', err.message || 'Could not place dine-in order. Please call staff.');
    },
  });

  if (isLoading) {
    return <LoadingState message="Loading restaurant dine-in menu..." />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => refetch()} />;
  }

  const categories = data?.categories ?? [];
  const uncategorized = data?.uncategorized ?? [];

  const allItems: any[] = [];
  categories.forEach((c: any) => {
    (c.menu_items || []).forEach((it: any) => {
      allItems.push({ ...it, category_name: c.name, cat_id: c.id });
    });
  });
  uncategorized.forEach((it: any) => {
    allItems.push({ ...it, category_name: 'Other', cat_id: null });
  });

  const filteredItems = allItems.filter((i) => {
    const matchesCategory = selectedCategory === 'all' || i.cat_id === selectedCategory;
    const matchesSearch =
      !searchQuery.trim() ||
      i.name.toLowerCase().includes(searchQuery.toLowerCase().trim());
    return matchesCategory && matchesSearch;
  });

  const getItemTrayQty = (itemId: number) => {
    return tray
      .filter((t) => t.item_id === itemId)
      .reduce((acc, t) => acc + t.quantity, 0);
  };

  const addItemToTray = (item: any, variant?: any) => {
    const price = variant ? Number(variant.price) : Number(item.price);
    const itemName = variant ? `${item.name} (${variant.name})` : item.name;

    setTray((prev) => {
      const existing = prev.find(
        (t) => t.item_id === item.id && t.variant_id === (variant?.id || undefined)
      );
      if (existing) {
        return prev.map((t) =>
          t.item_id === item.id && t.variant_id === (variant?.id || undefined)
            ? { ...t, quantity: t.quantity + 1 }
            : t
        );
      }
      return [
        ...prev,
        {
          item_id: item.id,
          name: itemName,
          variant_id: variant?.id,
          variant_name: variant?.name,
          price,
          quantity: 1,
        },
      ];
    });
  };

  const handleDishPress = (item: any) => {
    if (item.variants && item.variants.length > 0) {
      setVariantItem(item);
    } else {
      addItemToTray(item);
    }
  };

  const removeFromTray = (itemId: number, variantId?: number) => {
    setTray((prev) => {
      const idx = prev.findIndex(
        (t) => t.item_id === itemId && t.variant_id === variantId
      );
      if (idx === -1) return prev;
      const current = prev[idx];
      if (current.quantity > 1) {
        const copy = [...prev];
        copy[idx] = { ...current, quantity: current.quantity - 1 };
        return copy;
      }
      return prev.filter((_, i) => i !== idx);
    });
  };

  const totalTrayQty = tray.reduce((sum, item) => sum + item.quantity, 0);
  const totalTrayPrice = tray.reduce((sum, item) => sum + item.price * item.quantity, 0);

  const activeTable = customTableInput.trim() || selectedTable;

  const handleSendToKitchen = () => {
    if (tray.length === 0) {
      Alert.alert('Empty Tray', 'Please select dishes before sending order.');
      return;
    }
    if (!activeTable) {
      Alert.alert('Table Required', 'Please select or enter your table number.');
      return;
    }

    placeDineInMutation.mutate({
      table_number: activeTable,
      customer_name: guestName.trim() || `Table ${activeTable} Guest`,
      customer_phone: '',
      notes: tableNotes.trim(),
      items: tray.map((t) => ({
        item_id: t.item_id,
        variant_id: t.variant_id || null,
        quantity: t.quantity,
      })),
    });
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* ── Top Header Bar ── */}
      <View style={styles.headerArea}>
        <View style={styles.headerTopRow}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={22} color="#0F172A" />
          </TouchableOpacity>

          <View style={{ flex: 1, marginLeft: 10 }}>
            <Text style={styles.restaurantName}>{data?.restaurant?.name || 'Grill Cafe'}</Text>
            <Text style={styles.restaurantSub}>Dine-In Table Menu</Text>
          </View>

          <View style={styles.currentTableBadge}>
            <Text style={styles.tableBadgeLabel}>TABLE</Text>
            <Text style={styles.tableBadgeNumber}>#{activeTable}</Text>
          </View>
        </View>

        {/* ── Table Number Selector ── */}
        <View style={styles.tablePickerSection}>
          <Text style={styles.tablePickerLabel}>Select Your Table:</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tableChipsRow}>
            {['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'].map((tbl) => {
              const active = selectedTable === tbl && !customTableInput;
              return (
                <TouchableOpacity
                  key={tbl}
                  onPress={() => {
                    setSelectedTable(tbl);
                    setCustomTableInput('');
                  }}
                  style={[
                    styles.tableChip,
                    active && styles.tableChipActive,
                  ]}
                >
                  <Text style={[styles.tableChipText, active && styles.tableChipTextActive]}>
                    T-{tbl}
                  </Text>
                </TouchableOpacity>
              );
            })}
            <TouchableOpacity
              onPress={() => setIsCustomTableOpen(true)}
              style={[
                styles.tableChip,
                customTableInput ? styles.tableChipActive : null,
              ]}
            >
              <Text style={[styles.tableChipText, customTableInput ? styles.tableChipTextActive : null]}>
                {customTableInput ? `T-${customTableInput}` : '+ Other'}
              </Text>
            </TouchableOpacity>
          </ScrollView>
        </View>

        {/* ── Search Bar ── */}
        <View style={styles.searchBarBox}>
          <Ionicons name="search" size={16} color="#94A3B8" />
          <TextInput
            placeholder="Search dishes, burgers, drinks..."
            placeholderTextColor="#94A3B8"
            value={searchQuery}
            onChangeText={setSearchQuery}
            style={styles.searchInput}
          />
          {searchQuery ? (
            <TouchableOpacity onPress={() => setSearchQuery('')}>
              <Ionicons name="close-circle" size={16} color="#94A3B8" />
            </TouchableOpacity>
          ) : null}
        </View>

        {/* ── Category Chips ── */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.categoryChipsRow}>
          <TouchableOpacity
            onPress={() => setSelectedCategory('all')}
            style={[
              styles.catChip,
              selectedCategory === 'all' && styles.catChipActive,
            ]}
          >
            <Text style={[styles.catChipText, selectedCategory === 'all' && styles.catChipTextActive]}>
              All ({allItems.length})
            </Text>
          </TouchableOpacity>

          {categories.map((c: any) => {
            const active = selectedCategory === c.id;
            return (
              <TouchableOpacity
                key={c.id}
                onPress={() => setSelectedCategory(c.id)}
                style={[
                  styles.catChip,
                  active && styles.catChipActive,
                ]}
              >
                <Text style={[styles.catChipText, active && styles.catChipTextActive]}>
                  {c.name}
                </Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>
      </View>

      {/* ── Dishes List ── */}
      <ScrollView contentContainerStyle={styles.dishesScroll}>
        <View style={styles.dishesGrid}>
          {filteredItems.map((item) => {
            const inTrayQty = getItemTrayQty(item.id);
            const hasVariants = item.variants && item.variants.length > 0;

            return (
              <View key={item.id} style={styles.dishCard}>
                <View style={styles.dishHeader}>
                  <View style={styles.dishIconBox}>
                    <Ionicons name="fast-food" size={24} color="#064E45" />
                  </View>
                  <View style={{ flex: 1, marginLeft: 10 }}>
                    <Text style={styles.dishName}>{item.name}</Text>
                    <Text style={styles.dishPrice}>Rs. {Number(item.price).toLocaleString()}</Text>
                  </View>
                </View>

                {item.description ? (
                  <Text style={styles.dishDesc} numberOfLines={2}>{item.description}</Text>
                ) : null}

                {/* Add / Stepper Button */}
                <View style={styles.dishBottomRow}>
                  {hasVariants ? (
                    <TouchableOpacity
                      onPress={() => handleDishPress(item)}
                      style={styles.variantSelectBtn}
                    >
                      <Text style={styles.variantSelectText}>Choose Options</Text>
                      <Ionicons name="chevron-forward" size={14} color="#064E45" />
                    </TouchableOpacity>
                  ) : inTrayQty > 0 ? (
                    <View style={styles.stepperWrap}>
                      <TouchableOpacity onPress={() => removeFromTray(item.id)} style={styles.stepperAction}>
                        <Ionicons name="remove" size={14} color="#FFFFFF" />
                      </TouchableOpacity>
                      <Text style={styles.stepperCount}>{inTrayQty}</Text>
                      <TouchableOpacity onPress={() => addItemToTray(item)} style={styles.stepperAction}>
                        <Ionicons name="add" size={14} color="#FFFFFF" />
                      </TouchableOpacity>
                    </View>
                  ) : (
                    <TouchableOpacity
                      onPress={() => addItemToTray(item)}
                      style={styles.addDishBtn}
                    >
                      <Ionicons name="add" size={16} color="#FFFFFF" />
                      <Text style={styles.addDishText}>Add</Text>
                    </TouchableOpacity>
                  )}
                </View>
              </View>
            );
          })}
        </View>
      </ScrollView>

      {/* ── Floating Table Tray Bottom Dock ── */}
      {totalTrayQty > 0 && (
        <View style={styles.trayDockWrapper}>
          <TouchableOpacity
            activeOpacity={0.9}
            onPress={() => setIsTrayModalOpen(true)}
            style={styles.trayDock}
          >
            <View style={styles.trayLeft}>
              <View style={styles.trayIconBadge}>
                <Ionicons name="restaurant" size={18} color="#FFFFFF" />
              </View>
              <View style={{ marginLeft: 10 }}>
                <Text style={styles.trayTitle}>{totalTrayQty} Dish{totalTrayQty > 1 ? 'es' : ''} • Table {activeTable}</Text>
                <Text style={styles.traySub}>Rs. {totalTrayPrice.toLocaleString()}</Text>
              </View>
            </View>

            <View style={styles.trayProceedBtn}>
              <Text style={styles.trayProceedText}>Review Table Order</Text>
              <Ionicons name="arrow-forward" size={16} color="#FFFFFF" />
            </View>
          </TouchableOpacity>
        </View>
      )}

      {/* ── Variant Picker Bottom Sheet ── */}
      {variantItem && (
        <Modal visible={!!variantItem} animationType="slide" transparent onRequestClose={() => setVariantItem(null)}>
          <View style={styles.modalOverlay}>
            <View style={styles.sheetCard}>
              <View style={styles.sheetHeader}>
                <View>
                  <Text style={styles.sheetTitle}>{variantItem.name}</Text>
                  <Text style={styles.sheetSub}>Select portion size or option:</Text>
                </View>
                <TouchableOpacity onPress={() => setVariantItem(null)}>
                  <Ionicons name="close-circle" size={26} color="#94A3B8" />
                </TouchableOpacity>
              </View>

              <View style={{ gap: 10, marginVertical: 14 }}>
                {variantItem.variants.map((v: any) => (
                  <TouchableOpacity
                    key={v.id}
                    onPress={() => {
                      addItemToTray(variantItem, v);
                      setVariantItem(null);
                    }}
                    style={styles.variantChoiceRow}
                  >
                    <Text style={styles.variantChoiceName}>{v.name}</Text>
                    <Text style={styles.variantChoicePrice}>Rs. {Number(v.price).toLocaleString()}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          </View>
        </Modal>
      )}

      {/* ── Table Order Review & Submit Modal ── */}
      <Modal visible={isTrayModalOpen} animationType="slide" transparent onRequestClose={() => setIsTrayModalOpen(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.sheetCard}>
            <View style={styles.sheetHeader}>
              <View>
                <Text style={styles.sheetTitle}>Table {activeTable} Order</Text>
                <Text style={styles.sheetSub}>Review dishes before sending to kitchen</Text>
              </View>
              <TouchableOpacity onPress={() => setIsTrayModalOpen(false)}>
                <Ionicons name="close-circle" size={26} color="#94A3B8" />
              </TouchableOpacity>
            </View>

            <ScrollView style={{ maxHeight: 280 }}>
              {tray.map((item, idx) => (
                <View key={`${item.name}-${idx}`} style={styles.trayItemRow}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.trayItemName}>{item.name}</Text>
                    <Text style={styles.trayItemPrice}>Rs. {item.price.toLocaleString()} each</Text>
                  </View>

                  <View style={styles.trayStepper}>
                    <TouchableOpacity onPress={() => removeFromTray(item.item_id, item.variant_id)} style={styles.trayStepBtn}>
                      <Ionicons name="remove" size={14} color="#064E45" />
                    </TouchableOpacity>
                    <Text style={styles.trayStepCount}>{item.quantity}</Text>
                    <TouchableOpacity onPress={() => addItemToTray({ id: item.item_id, name: item.name }, item.variant_id ? { id: item.variant_id, price: item.price } : undefined)} style={styles.trayStepBtn}>
                      <Ionicons name="add" size={14} color="#064E45" />
                    </TouchableOpacity>
                  </View>

                  <Text style={styles.trayItemTotal}>
                    Rs. {(item.price * item.quantity).toLocaleString()}
                  </Text>
                </View>
              ))}
            </ScrollView>

            {/* Special Instructions */}
            <View style={{ marginTop: 10 }}>
              <Text style={styles.inputLabel}>Kitchen Notes / Special Requests:</Text>
              <TextInput
                placeholder="e.g. Less spicy, bring drinks first, extra glasses..."
                placeholderTextColor="#94A3B8"
                value={tableNotes}
                onChangeText={setTableNotes}
                style={styles.textInputBox}
              />

              <TextInput
                placeholder="Your Name (Optional)"
                placeholderTextColor="#94A3B8"
                value={guestName}
                onChangeText={setGuestName}
                style={[styles.textInputBox, { marginTop: 6 }]}
              />
            </View>

            {/* Grand Total */}
            <View style={styles.grandTotalBar}>
              <Text style={styles.grandTotalLabel}>Table Total</Text>
              <Text style={styles.grandTotalVal}>Rs. {totalTrayPrice.toLocaleString()}</Text>
            </View>

            <View style={{ marginTop: 12 }}>
              <AppButton
                title={`🍽️ Send Order to Kitchen (Table ${activeTable})`}
                loading={placeDineInMutation.isPending}
                onPress={handleSendToKitchen}
              />
            </View>
          </View>
        </View>
      </Modal>

      {/* ── Custom Table Input Modal ── */}
      {isCustomTableOpen && (
        <Modal visible={isCustomTableOpen} animationType="fade" transparent onRequestClose={() => setIsCustomTableOpen(false)}>
          <View style={styles.modalOverlay}>
            <View style={[styles.sheetCard, { marginHorizontal: 20 }]}>
              <Text style={styles.sheetTitle}>Enter Table Number</Text>
              <TextInput
                placeholder="e.g. 12 or Terrace-3"
                placeholderTextColor="#94A3B8"
                value={customTableInput}
                onChangeText={setCustomTableInput}
                style={[styles.textInputBox, { marginTop: 12 }]}
              />
              <View style={{ flexDirection: 'row', gap: 10, marginTop: 14 }}>
                <TouchableOpacity
                  onPress={() => setIsCustomTableOpen(false)}
                  style={styles.cancelModalBtn}
                >
                  <Text style={styles.cancelModalText}>Cancel</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  onPress={() => setIsCustomTableOpen(false)}
                  style={styles.confirmModalBtn}
                >
                  <Text style={styles.confirmModalText}>Confirm Table</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>
      )}

      {/* ── Order Confirmed Success Screen ── */}
      {confirmedOrder && (
        <Modal visible={!!confirmedOrder} animationType="fade" transparent onRequestClose={() => setConfirmedOrder(null)}>
          <View style={styles.modalOverlay}>
            <View style={styles.confirmedCard}>
              <View style={styles.successIconCircle}>
                <Ionicons name="checkmark-done" size={40} color="#16A34A" />
              </View>

              <Text style={styles.confirmedTitle}>Order Sent to Kitchen! 🍳</Text>
              <Text style={styles.confirmedSub}>
                Table {confirmedOrder.table_number} • Token #{confirmedOrder.daily_order_number || confirmedOrder.id}
              </Text>

              <View style={styles.confirmedDetailsBox}>
                <Text style={styles.confirmedNoticeText}>
                  Your order has been transmitted directly to the kitchen queue. The chef is preparing your meal now.
                </Text>
                <Text style={styles.confirmedTotalText}>
                  Payable at counter/table: Rs. {Number(confirmedOrder.total).toLocaleString()}
                </Text>
              </View>

              <TouchableOpacity
                onPress={() => setConfirmedOrder(null)}
                style={styles.doneBtn}
              >
                <Text style={styles.doneBtnText}>Back to Table Menu</Text>
              </TouchableOpacity>
            </View>
          </View>
        </Modal>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFF8EF' },
  headerArea: { backgroundColor: '#FFFFFF', paddingHorizontal: 16, paddingTop: 10, paddingBottom: 10, borderBottomWidth: 1, borderBottomColor: '#E8DEC9' },
  headerTopRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 },
  backBtn: { width: 38, height: 38, borderRadius: 12, backgroundColor: '#F9F1E6', alignItems: 'center', justifyContent: 'center' },
  restaurantName: { fontSize: 18, fontWeight: '800', color: '#064E45' },
  restaurantSub: { fontSize: 12, color: '#81958C' },
  currentTableBadge: { backgroundColor: '#E8F5F2', borderWidth: 1, borderColor: '#064E45', borderRadius: 12, paddingHorizontal: 10, paddingVertical: 4, alignItems: 'center' },
  tableBadgeLabel: { fontSize: 9, fontWeight: '800', color: '#064E45' },
  tableBadgeNumber: { fontSize: 14, fontWeight: '900', color: '#064E45' },
  tablePickerSection: { marginBottom: 10 },
  tablePickerLabel: { fontSize: 11, fontWeight: '700', color: '#81958C', marginBottom: 6 },
  tableChipsRow: { gap: 6 },
  tableChip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 10, backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#E8DEC9' },
  tableChipActive: { backgroundColor: '#064E45', borderColor: '#064E45' },
  tableChipText: { fontSize: 12, fontWeight: '700', color: '#81958C' },
  tableChipTextActive: { color: '#FFFFFF' },
  searchBarBox: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#E8DEC9', borderRadius: 12, paddingHorizontal: 10, height: 38, gap: 6, marginBottom: 8 },
  searchInput: { flex: 1, fontSize: 13, color: '#0C2621' },
  categoryChipsRow: { gap: 6 },
  catChip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 14, backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#E8DEC9' },
  catChipActive: { backgroundColor: '#064E45', borderColor: '#064E45' },
  catChipText: { fontSize: 12, fontWeight: '600', color: '#81958C' },
  catChipTextActive: { color: '#FFFFFF', fontWeight: '700' },
  dishesScroll: { padding: 14, paddingBottom: 90 },
  dishesGrid: { gap: 10 },
  dishCard: { backgroundColor: '#FFFFFF', borderRadius: 16, padding: 14, borderWidth: 1, borderColor: '#E8DEC9', elevation: 1 },
  dishHeader: { flexDirection: 'row', alignItems: 'center' },
  dishIconBox: { width: 44, height: 44, borderRadius: 12, backgroundColor: '#E8F5F2', alignItems: 'center', justifyContent: 'center' },
  dishName: { fontSize: 15, fontWeight: '700', color: '#0C2621' },
  dishPrice: { fontSize: 13, fontWeight: '800', color: '#064E45', marginTop: 2 },
  dishDesc: { fontSize: 12, color: '#81958C', marginTop: 6 },
  dishBottomRow: { flexDirection: 'row', justifyContent: 'flex-end', marginTop: 10 },
  addDishBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#064E45', paddingHorizontal: 14, paddingVertical: 6, borderRadius: 10, gap: 4 },
  addDishText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700' },
  variantSelectBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#E8F5F2', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 10, gap: 4 },
  variantSelectText: { color: '#064E45', fontSize: 12, fontWeight: '700' },
  stepperWrap: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#064E45', borderRadius: 12, paddingHorizontal: 4, height: 32 },
  stepperAction: { width: 24, height: 24, alignItems: 'center', justifyContent: 'center' },
  stepperCount: { color: '#FFFFFF', fontWeight: '800', fontSize: 13, paddingHorizontal: 6 },
  trayDockWrapper: { position: 'absolute', bottom: Platform.OS === 'ios' ? 34 : 20, left: 14, right: 14 },
  trayDock: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: '#003C35', paddingVertical: 12, paddingHorizontal: 16, borderRadius: 20, elevation: 8 },
  trayLeft: { flexDirection: 'row', alignItems: 'center' },
  trayIconBadge: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#FF941F', alignItems: 'center', justifyContent: 'center' },
  trayTitle: { color: '#FFFFFF', fontSize: 13, fontWeight: '700' },
  traySub: { color: '#B0C2BA', fontSize: 12 },
  trayProceedBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FF941F', paddingVertical: 8, paddingHorizontal: 12, borderRadius: 12, gap: 6 },
  trayProceedText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700' },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  sheetCard: { backgroundColor: '#FFFFFF', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 18, maxHeight: '90%' },
  sheetHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  sheetTitle: { fontSize: 18, fontWeight: '800', color: '#064E45' },
  sheetSub: { fontSize: 12, color: '#81958C', marginTop: 2 },
  variantChoiceRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 14, borderRadius: 12, borderWidth: 1, borderColor: '#E8DEC9', backgroundColor: '#FFF8EF' },
  variantChoiceName: { fontSize: 14, fontWeight: '700', color: '#0C2621' },
  variantChoicePrice: { fontSize: 14, fontWeight: '800', color: '#064E45' },
  trayItemRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: '#F9F1E6' },
  trayItemName: { fontSize: 14, fontWeight: '700', color: '#0C2621' },
  trayItemPrice: { fontSize: 11, color: '#81958C' },
  trayStepper: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#E8F5F2', borderRadius: 8, paddingHorizontal: 4, height: 28 },
  trayStepBtn: { width: 22, height: 22, alignItems: 'center', justifyContent: 'center' },
  trayStepCount: { fontSize: 12, fontWeight: '800', color: '#064E45', paddingHorizontal: 6 },
  trayItemTotal: { fontSize: 13, fontWeight: '800', color: '#0C2621', minWidth: 65, textAlign: 'right' },
  inputLabel: { fontSize: 12, fontWeight: '700', color: '#064E45', marginBottom: 4 },
  textInputBox: { height: 42, borderWidth: 1, borderColor: '#E8DEC9', borderRadius: 10, paddingHorizontal: 10, fontSize: 13, color: '#0C2621', backgroundColor: '#FFF8EF' },
  grandTotalBar: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', borderTopWidth: 1, borderTopColor: '#E8DEC9', paddingTop: 10, marginTop: 10 },
  grandTotalLabel: { fontSize: 16, fontWeight: '800', color: '#064E45' },
  grandTotalVal: { fontSize: 18, fontWeight: '900', color: '#064E45' },
  cancelModalBtn: { flex: 1, paddingVertical: 10, borderRadius: 10, borderWidth: 1, borderColor: '#E8DEC9', alignItems: 'center' },
  cancelModalText: { fontSize: 13, fontWeight: '600', color: '#81958C' },
  confirmModalBtn: { flex: 1, backgroundColor: '#064E45', paddingVertical: 10, borderRadius: 10, alignItems: 'center' },
  confirmModalText: { fontSize: 13, fontWeight: '700', color: '#FFFFFF' },
  confirmedCard: { backgroundColor: '#FFFFFF', borderRadius: 24, padding: 24, alignItems: 'center', margin: 20 },
  successIconCircle: { width: 72, height: 72, borderRadius: 36, backgroundColor: '#E8F5F2', alignItems: 'center', justifyContent: 'center', marginBottom: 14 },
  confirmedTitle: { fontSize: 20, fontWeight: '900', color: '#064E45', textAlign: 'center' },
  confirmedSub: { fontSize: 14, fontWeight: '700', color: '#064E45', marginTop: 4, marginBottom: 14 },
  confirmedDetailsBox: { backgroundColor: '#FFF8EF', borderRadius: 14, padding: 14, width: '100%', marginBottom: 16, gap: 8 },
  confirmedNoticeText: { fontSize: 12, color: '#81958C', textAlign: 'center', lineHeight: 18 },
  confirmedTotalText: { fontSize: 14, fontWeight: '800', color: '#064E45', textAlign: 'center' },
  doneBtn: { backgroundColor: '#064E45', width: '100%', paddingVertical: 14, borderRadius: 14, alignItems: 'center' },
  doneBtnText: { color: '#FFFFFF', fontSize: 14, fontWeight: '700' },
});
