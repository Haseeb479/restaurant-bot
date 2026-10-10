import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Modal,
  Alert,
  Linking,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';

interface BillItem {
  item_id?: number;
  name: string;
  variant_name?: string;
  unit_price: number;
  quantity: number;
  subtotal: number;
}

export default function PosBillingTerminalScreen() {
  const { theme } = useAppTheme();
  const queryClient = useQueryClient();

  // Top Mode Segment: 'billing' | 'today_bills' | 'keypad'
  const [terminalMode, setTerminalMode] = useState<'billing' | 'today_bills' | 'keypad'>('billing');

  // Active Bill State (Zero e-commerce shopping cart, direct counter bill)
  const [billItems, setBillItems] = useState<BillItem[]>([]);
  const [orderType, setOrderType] = useState<'dine_in' | 'takeaway' | 'delivery'>('dine_in');
  const [tableNumber, setTableNumber] = useState('T1');
  const [customTable, setCustomTable] = useState('');
  const [paymentMethod, setPaymentMethod] = useState<'cash' | 'card' | 'online'>('cash');
  const [discountPercent, setDiscountPercent] = useState<number>(0);
  const [applyTax, setApplyTax] = useState<boolean>(true);
  const [cashTendered, setCashTendered] = useState<string>('');
  const [billNotes, setBillNotes] = useState('');

  // Optional customer lookup
  const [customerPhone, setCustomerPhone] = useState('');
  const [customerName, setCustomerName] = useState('');
  const [deliveryAddress, setDeliveryAddress] = useState('');
  const [isSearchingCustomer, setIsSearchingCustomer] = useState(false);

  // Search & Catalog
  const [selectedCategory, setSelectedCategory] = useState<number | 'all'>('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Custom Item Modal
  const [isCustomItemOpen, setIsCustomItemOpen] = useState(false);
  const [customItemName, setCustomItemName] = useState('');
  const [customItemPrice, setCustomItemPrice] = useState('');

  // Keypad Quick Charge State
  const [keypadAmount, setKeypadAmount] = useState('');
  const [keypadLabel, setKeypadLabel] = useState('Special Food Charge');

  // Success Bill Modal
  const [completedBill, setCompletedBill] = useState<any | null>(null);
  const [completedChangeDue, setCompletedChangeDue] = useState<number>(0);

  // Search in Today's Bills
  const [billsSearch, setBillsSearch] = useState('');

  // 1. Fetch Catalog
  const { data: catalogData, isLoading: isCatalogLoading, error: catalogError, refetch: refetchCatalog } = useQuery({
    queryKey: ['pos-catalog'],
    queryFn: () => apiClient<any>('/pos/catalog'),
  });

  // 2. Fetch Today's Register Summary & Bills
  const { data: summaryData, refetch: refetchSummary } = useQuery({
    queryKey: ['pos-summary'],
    queryFn: () => apiClient<any>('/pos/summary'),
    refetchInterval: 10000,
  });

  // Customer Phone Auto-Lookup
  useEffect(() => {
    const cleanPhone = customerPhone.trim();
    if (cleanPhone.length >= 7) {
      const timer = setTimeout(async () => {
        try {
          setIsSearchingCustomer(true);
          const res = await apiClient<any>(`/customers?search=${encodeURIComponent(cleanPhone)}`);
          setIsSearchingCustomer(false);
          if (res?.customers && res.customers.length > 0) {
            const match = res.customers[0];
            if (!customerName) setCustomerName(match.name || '');
            if (!deliveryAddress && match.address) setDeliveryAddress(match.address);
          }
        } catch {
          setIsSearchingCustomer(false);
        }
      }, 400);
      return () => clearTimeout(timer);
    }
  }, [customerPhone]);

  // Mutations
  const createBillMutation = useMutation({
    mutationFn: (payload: any) =>
      apiClient('/pos/orders', {
        method: 'POST',
        body: JSON.stringify(payload),
      }),
    onSuccess: (res: any) => {
      const order = res?.order;
      setCompletedBill(order);
      setCompletedChangeDue(res?.change_due || 0);

      // Reset bill lines for next counter customer
      setBillItems([]);
      setCashTendered('');
      setBillNotes('');
      setDiscountPercent(0);
      setCustomerPhone('');
      setCustomerName('');
      setDeliveryAddress('');

      queryClient.invalidateQueries({ queryKey: ['pos-summary'] });
      queryClient.invalidateQueries({ queryKey: ['orders-pipeline'] });
      queryClient.invalidateQueries({ queryKey: ['command-center'] });
    },
    onError: (err: any) => {
      Alert.alert('Billing Error', err.message || 'Could not punch bill.');
    },
  });

  const voidBillMutation = useMutation({
    mutationFn: (orderId: number) =>
      apiClient(`/pos/orders/${orderId}/void`, { method: 'POST' }),
    onSuccess: (res: any) => {
      Alert.alert('Bill Voided', res.message || 'Bill has been cancelled.');
      queryClient.invalidateQueries({ queryKey: ['pos-summary'] });
      queryClient.invalidateQueries({ queryKey: ['orders-pipeline'] });
      queryClient.invalidateQueries({ queryKey: ['command-center'] });
    },
    onError: (err: any) => {
      Alert.alert('Void Error', err.message || 'Could not void bill.');
    },
  });

  if (isCatalogLoading) return <LoadingState message="Starting Owner Billing Terminal..." />;
  if (catalogError) return <ErrorState message={catalogError.message} onRetry={() => refetchCatalog()} />;

  // Catalog items flattening
  const categories = catalogData?.categories ?? [];
  const uncategorized = catalogData?.uncategorized ?? [];

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

  // Direct 1-Tap Item Punch to Bill
  const punchItem = (item: any) => {
    setBillItems((prev) => {
      const existingIdx = prev.findIndex((b) => b.item_id === item.id);
      if (existingIdx !== -1) {
        const copy = [...prev];
        const existing = copy[existingIdx];
        const newQty = existing.quantity + 1;
        copy[existingIdx] = {
          ...existing,
          quantity: newQty,
          subtotal: existing.unit_price * newQty,
        };
        return copy;
      }
      const price = Number(item.price);
      return [
        ...prev,
        {
          item_id: item.id,
          name: item.name,
          unit_price: price,
          quantity: 1,
          subtotal: price,
        },
      ];
    });
  };

  const updateBillQty = (index: number, delta: number) => {
    setBillItems((prev) => {
      const copy = [...prev];
      const item = copy[index];
      const newQty = item.quantity + delta;
      if (newQty <= 0) {
        return copy.filter((_, idx) => idx !== index);
      }
      copy[index] = {
        ...item,
        quantity: newQty,
        subtotal: item.unit_price * newQty,
      };
      return copy;
    });
  };

  const removeBillItem = (index: number) => {
    setBillItems((prev) => prev.filter((_, idx) => idx !== index));
  };

  const punchCustomItem = (name: string, price: number) => {
    if (!price || price <= 0) return;
    setBillItems((prev) => [
      ...prev,
      {
        name: name.trim() || 'Custom Food Item',
        unit_price: price,
        quantity: 1,
        subtotal: price,
      },
    ]);
  };

  // Bill Calculations
  const rawSubtotal = billItems.reduce((acc, it) => acc + it.subtotal, 0);
  const discountAmount = Math.round((rawSubtotal * discountPercent) / 100);
  const taxableSubtotal = Math.max(0, rawSubtotal - discountAmount);
  const taxAmount = applyTax ? Math.round(taxableSubtotal * 0.05) : 0;
  const deliveryFee = orderType === 'delivery' ? Number(catalogData?.settings?.delivery_charge || 0) : 0;
  const grandPayable = taxableSubtotal + taxAmount + deliveryFee;

  // Tender & Change return
  const tenderedNum = Number(cashTendered || 0);
  const liveChange = tenderedNum > grandPayable ? tenderedNum - grandPayable : 0;

  // 1-Tap Bill Punch Submission
  const handlePunchBill = () => {
    if (billItems.length === 0) {
      Alert.alert('Empty Bill', 'Tap menu items to add them to the bill.');
      return;
    }

    const payload = {
      customer_name: customerName.trim() || 'Counter Guest',
      customer_phone: customerPhone.trim() || 'Walk-in',
      delivery_type: orderType,
      table_number: orderType === 'dine_in' ? (customTable.trim() || tableNumber) : null,
      delivery_address:
        orderType === 'delivery'
          ? deliveryAddress.trim() || 'Counter Delivery'
          : orderType === 'dine_in'
          ? `Dine-In (${customTable.trim() || tableNumber})`
          : 'Takeaway Counter',
      payment_method: paymentMethod,
      discount_amount: discountAmount,
      cash_tendered: paymentMethod === 'cash' && tenderedNum > 0 ? tenderedNum : null,
      notes: billNotes.trim(),
      items: billItems.map((b) => ({
        item_id: b.item_id || null,
        name: b.name,
        price: b.unit_price,
        quantity: b.quantity,
      })),
    };

    createBillMutation.mutate(payload);
  };

  // WhatsApp Bill Sender
  const sendWhatsAppBill = (order: any) => {
    const phone = (order.customer_phone || '').replace(/[^0-9]/g, '');
    const itemsText = (order.items || [])
      .map((i: any) => `• ${i.quantity}x ${i.name} - Rs. ${Number(i.subtotal || i.unit_price * i.quantity).toLocaleString()}`)
      .join('\n');
    const msg = encodeURIComponent(
      `*🧾 OFFICIAL BILL RECEIPT*\n` +
      `Bill: #${order.daily_order_number || order.id}\n` +
      `Order Type: ${order.delivery_address || 'Counter'}\n\n` +
      `*Items:*\n${itemsText}\n\n` +
      `Subtotal: Rs. ${Number(order.subtotal || 0).toLocaleString()}\n` +
      (order.delivery_charge > 0 ? `Delivery: Rs. ${Number(order.delivery_charge).toLocaleString()}\n` : '') +
      `*Grand Total: Rs. ${Number(order.total || 0).toLocaleString()}*\n` +
      `Paid via: ${order.payment_method?.toUpperCase()} (PAID)\n\n` +
      `Thank you for visiting! Have a wonderful meal!`
    );
    Linking.openURL(`https://wa.me/${phone}?text=${msg}`).catch(() => {
      Alert.alert('Error', 'Could not open WhatsApp on this phone.');
    });
  };

  // Handle Keypad Entry
  const handleKeypadPress = (val: string) => {
    if (val === 'C') {
      setKeypadAmount('');
    } else if (val === 'DEL') {
      setKeypadAmount((prev) => prev.slice(0, -1));
    } else {
      setKeypadAmount((prev) => prev + val);
    }
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Top Owner Shift Register Bar (Today's Register Context) ── */}
      <View style={[styles.registerBar, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
        <View style={styles.registerRow}>
          <View>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
              <Ionicons name="cash" size={18} color="#16A34A" />
              <Text style={[styles.registerTitle, { color: theme.text }]}>Counter Billing Terminal</Text>
            </View>
            <Text style={[styles.registerSub, { color: theme.textMuted }]}>
              Today: {summaryData?.today_bills_count || 0} Bills • Rs. {Number(summaryData?.today_sales || 0).toLocaleString()} (Cash: Rs. {Number(summaryData?.cash_sales || 0).toLocaleString()})
            </Text>
          </View>

          <TouchableOpacity onPress={() => refetchSummary()} style={[styles.syncBtn, { backgroundColor: theme.surfaceSubtle }]}>
            <Ionicons name="refresh" size={15} color={theme.text} />
          </TouchableOpacity>
        </View>

        {/* ── 3-Tab Terminal Mode Selector ── */}
        <View style={[styles.terminalTabsRow, { backgroundColor: theme.surfaceSubtle }]}>
          <TouchableOpacity
            onPress={() => setTerminalMode('billing')}
            style={[styles.terminalTab, terminalMode === 'billing' && [styles.terminalTabActive, { backgroundColor: theme.surface }]]}
          >
            <Ionicons name="flash" size={15} color={terminalMode === 'billing' ? theme.primary : theme.textMuted} />
            <Text style={[styles.terminalTabText, { color: terminalMode === 'billing' ? theme.primary : theme.textMuted }]}>
              Quick Billing
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setTerminalMode('today_bills')}
            style={[styles.terminalTab, terminalMode === 'today_bills' && [styles.terminalTabActive, { backgroundColor: theme.surface }]]}
          >
            <Ionicons name="receipt" size={15} color={terminalMode === 'today_bills' ? theme.primary : theme.textMuted} />
            <Text style={[styles.terminalTabText, { color: terminalMode === 'today_bills' ? theme.primary : theme.textMuted }]}>
              Today's Bills ({summaryData?.today_bills_count || 0})
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setTerminalMode('keypad')}
            style={[styles.terminalTab, terminalMode === 'keypad' && [styles.terminalTabActive, { backgroundColor: theme.surface }]]}
          >
            <Ionicons name="keypad" size={15} color={terminalMode === 'keypad' ? theme.primary : theme.textMuted} />
            <Text style={[styles.terminalTabText, { color: terminalMode === 'keypad' ? theme.primary : theme.textMuted }]}>
              Custom Amount
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* ── Mode 1: Quick Counter Billing ── */}
      {terminalMode === 'billing' && (
        <ScrollView style={{ flex: 1 }} keyboardShouldPersistTaps="handled">
          {/* Order Type & Table Bar */}
          <View style={[styles.orderSetupBar, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
            <View style={styles.orderTypePillGroup}>
              {[
                { key: 'dine_in', label: 'Dine-In', icon: 'restaurant-outline' },
                { key: 'takeaway', label: 'Takeaway', icon: 'bag-handle-outline' },
                { key: 'delivery', label: 'Delivery', icon: 'bicycle-outline' },
              ].map((t) => {
                const active = orderType === t.key;
                return (
                  <TouchableOpacity
                    key={t.key}
                    onPress={() => setOrderType(t.key as any)}
                    style={[
                      styles.orderTypePill,
                      active ? { backgroundColor: theme.primary } : { backgroundColor: theme.surfaceSubtle },
                    ]}
                  >
                    <Ionicons name={t.icon as any} size={14} color={active ? '#FFFFFF' : theme.textMuted} />
                    <Text style={[styles.orderTypeText, { color: active ? '#FFFFFF' : theme.textMuted }]}>
                      {t.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            {/* Table Selection if Dine-In */}
            {orderType === 'dine_in' && (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tableChipsRow}>
                {['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8'].map((tbl) => {
                  const active = tableNumber === tbl && !customTable;
                  return (
                    <TouchableOpacity
                      key={tbl}
                      onPress={() => {
                        setTableNumber(tbl);
                        setCustomTable('');
                      }}
                      style={[
                        styles.tableChip,
                        active
                          ? { backgroundColor: theme.primaryLight, borderColor: theme.primary, borderWidth: 1.5 }
                          : { backgroundColor: theme.surfaceSubtle, borderColor: theme.border, borderWidth: 1 },
                      ]}
                    >
                      <Text style={[styles.tableChipText, { color: active ? theme.primary : theme.text }]}>
                        {tbl}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>
            )}
          </View>

          {/* Quick Menu Item Tiles (Direct 1-Tap Punch) */}
          <View style={[styles.menuPunchSection, { backgroundColor: theme.surface }]}>
            {/* Category Chips */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.catChipsRow}>
              <TouchableOpacity
                onPress={() => setSelectedCategory('all')}
                style={[
                  styles.catChip,
                  selectedCategory === 'all' ? { backgroundColor: theme.primary } : { backgroundColor: theme.surfaceSubtle },
                ]}
              >
                <Text style={[styles.catChipText, { color: selectedCategory === 'all' ? '#FFFFFF' : theme.textMuted }]}>
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
                      active ? { backgroundColor: theme.primary } : { backgroundColor: theme.surfaceSubtle },
                    ]}
                  >
                    <Text style={[styles.catChipText, { color: active ? '#FFFFFF' : theme.textMuted }]}>
                      {c.name}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>

            {/* Quick Dishes Grid (Compact, dense tiles for 1-tap counter punch) */}
            <View style={styles.dishesGrid}>
              {filteredItems.slice(0, 18).map((it) => {
                const inBill = billItems.find((b) => b.item_id === it.id);
                return (
                  <TouchableOpacity
                    key={it.id}
                    activeOpacity={0.7}
                    onPress={() => punchItem(it)}
                    style={[
                      styles.dishPunchTile,
                      { backgroundColor: theme.surfaceSubtle, borderColor: inBill ? theme.primary : theme.border },
                      inBill && { borderWidth: 1.5, backgroundColor: theme.primaryLight },
                    ]}
                  >
                    <View style={styles.tileHeader}>
                      <Text style={[styles.tilePrice, { color: theme.primary }]}>
                        Rs. {Number(it.price).toLocaleString()}
                      </Text>
                      {inBill ? (
                        <View style={[styles.tileQtyBadge, { backgroundColor: theme.primary }]}>
                          <Text style={styles.tileQtyText}>x{inBill.quantity}</Text>
                        </View>
                      ) : null}
                    </View>
                    <Text style={[styles.tileName, { color: theme.text }]} numberOfLines={2}>
                      {it.name}
                    </Text>
                  </TouchableOpacity>
                );
              })}

              {/* Add Custom Item Tile */}
              <TouchableOpacity
                onPress={() => setIsCustomItemOpen(true)}
                style={[styles.dishPunchTile, styles.customTile, { borderColor: theme.primary }]}
              >
                <Ionicons name="add-circle" size={20} color={theme.primary} />
                <Text style={[styles.customTileText, { color: theme.primary }]}>+ Custom Item</Text>
              </TouchableOpacity>
            </View>
          </View>

          {/* ── LIVE ACTIVE BILL SLIP (Running Counter Bill) ── */}
          <View style={[styles.runningBillCard, { backgroundColor: theme.surface, borderColor: theme.border }]}>
            <View style={styles.billSlipHeader}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                <Ionicons name="receipt-outline" size={18} color={theme.text} />
                <Text style={[styles.billSlipTitle, { color: theme.text }]}>Active Bill Ticket</Text>
                <Text style={[styles.billCountBadge, { backgroundColor: theme.surfaceSubtle, color: theme.textMuted }]}>
                  {billItems.reduce((acc, it) => acc + it.quantity, 0)} Items
                </Text>
              </View>

              {billItems.length > 0 && (
                <TouchableOpacity
                  onPress={() => {
                    Alert.alert('Void Draft Bill?', 'Clear all items from this ticket?', [
                      { text: 'Cancel', style: 'cancel' },
                      { text: 'Clear', style: 'destructive', onPress: () => setBillItems([]) },
                    ]);
                  }}
                  style={styles.clearBillBtn}
                >
                  <Ionicons name="trash-outline" size={14} color="#DC2626" />
                  <Text style={styles.clearBillText}>Clear</Text>
                </TouchableOpacity>
              )}
            </View>

            {/* Bill Lines */}
            {billItems.length === 0 ? (
              <View style={styles.emptyBillNotice}>
                <Ionicons name="restaurant-outline" size={28} color={theme.textMuted} />
                <Text style={[styles.emptyBillText, { color: theme.textMuted }]}>
                  Tap any menu item above or punch custom amount to start bill.
                </Text>
              </View>
            ) : (
              <View style={styles.billItemsList}>
                {billItems.map((item, idx) => (
                  <View key={`${item.name}-${idx}`} style={[styles.billItemRow, { borderBottomColor: theme.border }]}>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.billItemName, { color: theme.text }]}>{item.name}</Text>
                      <Text style={[styles.billItemPrice, { color: theme.textMuted }]}>
                        Rs. {item.unit_price.toLocaleString()} each
                      </Text>
                    </View>

                    {/* Stepper */}
                    <View style={[styles.stepperBox, { backgroundColor: theme.surfaceSubtle }]}>
                      <TouchableOpacity onPress={() => updateBillQty(idx, -1)} style={styles.stepBtn}>
                        <Ionicons name="remove" size={14} color={theme.text} />
                      </TouchableOpacity>
                      <Text style={[styles.stepCount, { color: theme.text }]}>{item.quantity}</Text>
                      <TouchableOpacity onPress={() => updateBillQty(idx, 1)} style={styles.stepBtn}>
                        <Ionicons name="add" size={14} color={theme.text} />
                      </TouchableOpacity>
                    </View>

                    <Text style={[styles.billItemSubtotal, { color: theme.text }]}>
                      Rs. {item.subtotal.toLocaleString()}
                    </Text>

                    <TouchableOpacity onPress={() => removeBillItem(idx)} style={{ marginLeft: 8 }}>
                      <Ionicons name="close-circle-outline" size={18} color="#EF4444" />
                    </TouchableOpacity>
                  </View>
                ))}
              </View>
            )}

            {/* Discounts & Tax Controls */}
            {billItems.length > 0 && (
              <View style={[styles.adjustmentsBox, { backgroundColor: theme.surfaceSubtle }]}>
                {/* Discount Pills */}
                <View style={styles.adjRow}>
                  <Text style={[styles.adjLabel, { color: theme.textMuted }]}>Discount:</Text>
                  <View style={styles.pillsRow}>
                    {[0, 5, 10, 15].map((d) => (
                      <TouchableOpacity
                        key={d}
                        onPress={() => setDiscountPercent(d)}
                        style={[
                          styles.adjPill,
                          discountPercent === d
                            ? { backgroundColor: theme.primary }
                            : { backgroundColor: theme.surface, borderColor: theme.border, borderWidth: 1 },
                        ]}
                      >
                        <Text style={[styles.adjPillText, { color: discountPercent === d ? '#FFFFFF' : theme.text }]}>
                          {d === 0 ? '0%' : `${d}%`}
                        </Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                </View>

                {/* Tax Toggle */}
                <View style={[styles.adjRow, { marginTop: 6 }]}>
                  <Text style={[styles.adjLabel, { color: theme.textMuted }]}>GST Tax:</Text>
                  <View style={styles.pillsRow}>
                    <TouchableOpacity
                      onPress={() => setApplyTax(false)}
                      style={[
                        styles.adjPill,
                        !applyTax
                          ? { backgroundColor: theme.primary }
                          : { backgroundColor: theme.surface, borderColor: theme.border, borderWidth: 1 },
                      ]}
                    >
                      <Text style={[styles.adjPillText, { color: !applyTax ? '#FFFFFF' : theme.text }]}>No Tax</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                      onPress={() => setApplyTax(true)}
                      style={[
                        styles.adjPill,
                        applyTax
                          ? { backgroundColor: theme.primary }
                          : { backgroundColor: theme.surface, borderColor: theme.border, borderWidth: 1 },
                      ]}
                    >
                      <Text style={[styles.adjPillText, { color: applyTax ? '#FFFFFF' : theme.text }]}>5% GST</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              </View>
            )}

            {/* Totals Summary */}
            <View style={styles.totalsSection}>
              <View style={styles.totalLine}>
                <Text style={{ color: theme.textMuted }}>Subtotal</Text>
                <Text style={{ color: theme.text, fontWeight: '600' }}>Rs. {rawSubtotal.toLocaleString()}</Text>
              </View>
              {discountAmount > 0 && (
                <View style={styles.totalLine}>
                  <Text style={{ color: '#16A34A' }}>Discount ({discountPercent}%)</Text>
                  <Text style={{ color: '#16A34A', fontWeight: '700' }}>- Rs. {discountAmount.toLocaleString()}</Text>
                </View>
              )}
              {taxAmount > 0 && (
                <View style={styles.totalLine}>
                  <Text style={{ color: theme.textMuted }}>Tax (5%)</Text>
                  <Text style={{ color: theme.text, fontWeight: '600' }}>Rs. {taxAmount.toLocaleString()}</Text>
                </View>
              )}
              {deliveryFee > 0 && (
                <View style={styles.totalLine}>
                  <Text style={{ color: theme.textMuted }}>Delivery Fee</Text>
                  <Text style={{ color: theme.text, fontWeight: '600' }}>Rs. {deliveryFee.toLocaleString()}</Text>
                </View>
              )}

              {/* Huge Bold Grand Total */}
              <View style={[styles.totalLine, styles.grandLine, { borderTopColor: theme.border }]}>
                <Text style={[styles.grandText, { color: theme.text }]}>Total Payable</Text>
                <Text style={[styles.grandAmount, { color: theme.primary }]}>
                  Rs. {grandPayable.toLocaleString()}
                </Text>
              </View>
            </View>

            {/* Payment Method Selector */}
            <View style={styles.payMethodsRow}>
              {[
                { key: 'cash', label: 'Cash', icon: 'cash' },
                { key: 'card', label: 'Card', icon: 'card' },
                { key: 'online', label: 'Online / QR', icon: 'qr-code' },
              ].map((pm) => {
                const active = paymentMethod === pm.key;
                return (
                  <TouchableOpacity
                    key={pm.key}
                    onPress={() => setPaymentMethod(pm.key as any)}
                    style={[
                      styles.payMethodBtn,
                      { borderColor: active ? theme.primary : theme.border },
                      active && { backgroundColor: theme.primaryLight },
                    ]}
                  >
                    <Ionicons name={pm.icon as any} size={18} color={active ? theme.primary : theme.textMuted} />
                    <Text style={[styles.payMethodText, { color: active ? theme.primary : theme.text }]}>
                      {pm.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            {/* Cash Tender & Change Return Calculator */}
            {paymentMethod === 'cash' && grandPayable > 0 && (
              <View style={[styles.tenderBox, { backgroundColor: '#F0FDF4', borderColor: '#BBF7D0' }]}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                  <Text style={{ fontSize: 12, fontWeight: '700', color: '#166534' }}>Cash Received:</Text>
                  <TextInput
                    placeholder="Enter amount (Rs.)"
                    placeholderTextColor="#86EFAC"
                    value={cashTendered}
                    onChangeText={setCashTendered}
                    keyboardType="numeric"
                    style={styles.tenderInput}
                  />
                </View>

                {/* Quick Note Suggestions */}
                <View style={styles.noteSuggestionsRow}>
                  {[
                    { label: `Exact (Rs. ${grandPayable})`, val: String(grandPayable) },
                    { label: '500', val: '500' },
                    { label: '1,000', val: '1000' },
                    { label: '2,000', val: '2000' },
                    { label: '5,000', val: '5000' },
                  ]
                    .filter((n) => Number(n.val) >= grandPayable || n.val === String(grandPayable))
                    .slice(0, 4)
                    .map((n) => (
                      <TouchableOpacity
                        key={n.label}
                        onPress={() => setCashTendered(n.val)}
                        style={styles.noteSuggestionBtn}
                      >
                        <Text style={styles.noteSuggestionText}>{n.label}</Text>
                      </TouchableOpacity>
                    ))}
                </View>

                {/* Change Return Live Result */}
                {liveChange > 0 && (
                  <View style={styles.changeDueRow}>
                    <Text style={styles.changeDueLabel}>RETURN CHANGE:</Text>
                    <Text style={styles.changeDueAmount}>Rs. {liveChange.toLocaleString()}</Text>
                  </View>
                )}
              </View>
            )}

            {/* Optional Customer Contact (for receipt / loyalty) */}
            <View style={styles.optionalCustomerBox}>
              <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                <Text style={{ fontSize: 11, fontWeight: '700', color: theme.textMuted }}>
                  Customer Phone (Optional for WhatsApp Receipt):
                </Text>
                {isSearchingCustomer && (
                  <Text style={{ fontSize: 10, color: theme.primary, fontWeight: '700' }}>Looking up...</Text>
                )}
              </View>
              <TextInput
                placeholder="03001234567 (Blank = Counter Walk-in)"
                placeholderTextColor={theme.textMuted}
                value={customerPhone}
                onChangeText={setCustomerPhone}
                keyboardType="phone-pad"
                style={[styles.phoneInput, { borderColor: theme.border, color: theme.text }]}
              />
            </View>

            {/* ── 1-TAP PUNCH BILL BUTTON (Instant Execution) ── */}
            <View style={{ marginTop: 12 }}>
              <AppButton
                title={
                  billItems.length === 0
                    ? 'Punch Bill'
                    : `⚡ PUNCH & PRINT BILL (Rs. ${grandPayable.toLocaleString()})`
                }
                loading={createBillMutation.isPending}
                disabled={billItems.length === 0}
                onPress={handlePunchBill}
              />
            </View>
          </View>
        </ScrollView>
      )}

      {/* ── Mode 2: Today's Bills (History, Reprint, Void) ── */}
      {terminalMode === 'today_bills' && (
        <View style={{ flex: 1, padding: 14 }}>
          {/* Search Box */}
          <View style={[styles.searchBox, { backgroundColor: theme.surfaceSubtle }]}>
            <Ionicons name="search" size={16} color={theme.textMuted} />
            <TextInput
              placeholder="Search by Bill # or customer..."
              placeholderTextColor={theme.textMuted}
              value={billsSearch}
              onChangeText={setBillsSearch}
              style={[styles.searchInput, { color: theme.text }]}
            />
          </View>

          {/* Bills List */}
          <ScrollView contentContainerStyle={{ paddingBottom: 60, paddingTop: 10 }}>
            {((summaryData?.recent_bills as any[]) || [])
              .filter((b) => {
                if (!billsSearch.trim()) return true;
                const q = billsSearch.toLowerCase().trim();
                return (
                  String(b.daily_order_number || b.id).includes(q) ||
                  (b.customer_name || '').toLowerCase().includes(q) ||
                  (b.customer_phone || '').includes(q)
                );
              })
              .map((bill) => {
                const isVoid = bill.status === 'cancelled';
                return (
                  <View
                    key={bill.id}
                    style={[
                      styles.pastBillCard,
                      { backgroundColor: theme.surface, borderColor: theme.border },
                      isVoid && { opacity: 0.6, borderColor: '#EF4444' },
                    ]}
                  >
                    <View style={styles.pastBillTop}>
                      <View>
                        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                          <Text style={[styles.pastBillNumber, { color: theme.text }]}>
                            Bill #{bill.daily_order_number || bill.id}
                          </Text>
                          <Text style={[styles.pastBillType, { backgroundColor: theme.surfaceSubtle, color: theme.textMuted }]}>
                            {bill.delivery_address}
                          </Text>
                          {isVoid && (
                            <Text style={styles.voidBadgeText}>VOIDED</Text>
                          )}
                        </View>
                        <Text style={[styles.pastBillTime, { color: theme.textMuted }]}>
                          {bill.created_at_time} • {bill.payment_method?.toUpperCase()}
                        </Text>
                      </View>
                      <Text style={[styles.pastBillTotal, { color: isVoid ? '#EF4444' : theme.primary }]}>
                        Rs. {Number(bill.total).toLocaleString()}
                      </Text>
                    </View>

                    <Text style={[styles.pastBillItems, { color: theme.textMuted }]} numberOfLines={2}>
                      {bill.items_summary}
                    </Text>

                    {/* Actions: Send WA, Reprint, Void */}
                    <View style={styles.pastBillActions}>
                      <TouchableOpacity
                        onPress={() => sendWhatsAppBill(bill)}
                        style={[styles.pastActionBtn, { backgroundColor: '#DCFCE7' }]}
                      >
                        <Ionicons name="logo-whatsapp" size={14} color="#16A34A" />
                        <Text style={[styles.pastActionText, { color: '#16A34A' }]}>Send WA Receipt</Text>
                      </TouchableOpacity>

                      <TouchableOpacity
                        onPress={() => Alert.alert('Thermal Receipt', `Receipt #${bill.daily_order_number || bill.id} printed to kitchen printer.`)}
                        style={[styles.pastActionBtn, { backgroundColor: theme.surfaceSubtle }]}
                      >
                        <Ionicons name="print" size={14} color={theme.text} />
                        <Text style={[styles.pastActionText, { color: theme.text }]}>Reprint Slip</Text>
                      </TouchableOpacity>

                      {!isVoid && (
                        <TouchableOpacity
                          onPress={() => {
                            Alert.alert(
                              'Void Bill?',
                              `Are you sure you want to VOID Bill #${bill.daily_order_number || bill.id}? This will deduct Rs. ${bill.total} from today's sales.`,
                              [
                                { text: 'Cancel', style: 'cancel' },
                                {
                                  text: 'Void Bill',
                                  style: 'destructive',
                                  onPress: () => voidBillMutation.mutate(bill.id),
                                },
                              ]
                            );
                          }}
                          style={[styles.pastActionBtn, { backgroundColor: '#FEE2E2' }]}
                        >
                          <Ionicons name="trash" size={14} color="#DC2626" />
                          <Text style={[styles.pastActionText, { color: '#DC2626' }]}>Void</Text>
                        </TouchableOpacity>
                      )}
                    </View>
                  </View>
                );
              })}
          </ScrollView>
        </View>
      )}

      {/* ── Mode 3: Custom Amount Numeric Keypad ── */}
      {terminalMode === 'keypad' && (
        <View style={[styles.keypadContainer, { backgroundColor: theme.surface }]}>
          <View style={[styles.keypadDisplayBox, { backgroundColor: theme.surfaceSubtle }]}>
            <Text style={[styles.keypadDisplayLabel, { color: theme.textMuted }]}>
              {keypadLabel || 'Custom Charge'}
            </Text>
            <Text style={[styles.keypadDisplayAmount, { color: theme.primary }]}>
              Rs. {keypadAmount || '0'}
            </Text>
          </View>

          {/* Quick Item Category Tags */}
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ maxHeight: 40, marginVertical: 8 }}>
            {['Special Platter', 'Buffet Charge', 'Extra Drinks', 'Catering Service', 'Party Hall'].map((tag) => (
              <TouchableOpacity
                key={tag}
                onPress={() => setKeypadLabel(tag)}
                style={[
                  styles.tagPill,
                  keypadLabel === tag ? { backgroundColor: theme.primary } : { backgroundColor: theme.surfaceSubtle },
                ]}
              >
                <Text style={[styles.tagPillText, { color: keypadLabel === tag ? '#FFFFFF' : theme.text }]}>
                  {tag}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>

          {/* Keypad Grid */}
          <View style={styles.numpadGrid}>
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
                    onPress={() => handleKeypadPress(btn)}
                    style={[styles.numKey, { backgroundColor: theme.surfaceSubtle, borderColor: theme.border }]}
                  >
                    <Text style={[styles.numKeyText, { color: btn === 'C' ? '#EF4444' : theme.text }]}>
                      {btn}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            ))}
          </View>

          {/* Add to Bill Button */}
          <View style={{ marginTop: 14 }}>
            <AppButton
              title={`Add Rs. ${keypadAmount || '0'} to Bill`}
              disabled={!keypadAmount || Number(keypadAmount) <= 0}
              onPress={() => {
                punchCustomItem(keypadLabel, Number(keypadAmount));
                setKeypadAmount('');
                setTerminalMode('billing');
              }}
            />
          </View>
        </View>
      )}

      {/* ── Custom Item Modal ── */}
      {isCustomItemOpen && (
        <Modal visible={isCustomItemOpen} animationType="fade" transparent onRequestClose={() => setIsCustomItemOpen(false)}>
          <View style={styles.modalBackdrop}>
            <View style={[styles.customItemDialog, { backgroundColor: theme.surface }]}>
              <Text style={[styles.dialogTitle, { color: theme.text }]}>Add Custom Food Item</Text>

              <Text style={[styles.inputLabel, { color: theme.text }]}>Item Description</Text>
              <TextInput
                placeholder="e.g. Special Grilled Fish"
                placeholderTextColor={theme.textMuted}
                value={customItemName}
                onChangeText={setCustomItemName}
                style={[styles.inputBox, { borderColor: theme.border, color: theme.text }]}
              />

              <Text style={[styles.inputLabel, { color: theme.text }]}>Price (Rs.) *</Text>
              <TextInput
                placeholder="e.g. 1450"
                placeholderTextColor={theme.textMuted}
                value={customItemPrice}
                onChangeText={setCustomItemPrice}
                keyboardType="numeric"
                style={[styles.inputBox, { borderColor: theme.border, color: theme.text }]}
              />

              <View style={{ flexDirection: 'row', gap: 10, marginTop: 14 }}>
                <TouchableOpacity
                  onPress={() => setIsCustomItemOpen(false)}
                  style={[styles.cancelBtn, { borderColor: theme.border }]}
                >
                  <Text style={[styles.cancelBtnText, { color: theme.text }]}>Cancel</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  onPress={() => {
                    punchCustomItem(customItemName, Number(customItemPrice));
                    setCustomItemName('');
                    setCustomItemPrice('');
                    setIsCustomItemOpen(false);
                  }}
                  style={[styles.saveBtn, { backgroundColor: theme.primary }]}
                >
                  <Text style={styles.saveBtnText}>Add to Bill</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>
      )}

      {/* ── Immediate Bill Punched Modal ── */}
      {completedBill && (
        <Modal visible={!!completedBill} animationType="fade" transparent onRequestClose={() => setCompletedBill(null)}>
          <View style={styles.modalBackdrop}>
            <View style={[styles.successSlipCard, { backgroundColor: theme.surface }]}>
              <View style={styles.successCheckCircle}>
                <Ionicons name="checkmark-done" size={36} color="#16A34A" />
              </View>

              <Text style={[styles.billPunchedTitle, { color: theme.text }]}>
                BILL #{completedBill.daily_order_number || completedBill.id} PUNCHED!
              </Text>
              <Text style={[styles.billPunchedSub, { color: theme.textMuted }]}>
                {completedBill.delivery_address} • Rs. {Number(completedBill.total).toLocaleString()} ({completedBill.payment_method?.toUpperCase()})
              </Text>

              {/* Change Return Highlight */}
              {completedChangeDue > 0 && (
                <View style={styles.changeDueBanner}>
                  <Text style={styles.changeDueBannerLabel}>RETURN CHANGE TO CUSTOMER:</Text>
                  <Text style={styles.changeDueBannerAmount}>Rs. {completedChangeDue.toLocaleString()}</Text>
                </View>
              )}

              {/* Actions */}
              <TouchableOpacity
                onPress={() => sendWhatsAppBill(completedBill)}
                style={[styles.successActionBtn, { backgroundColor: '#25D366' }]}
              >
                <Ionicons name="logo-whatsapp" size={18} color="#FFFFFF" />
                <Text style={styles.successActionText}>Send WhatsApp Receipt</Text>
              </TouchableOpacity>

              <TouchableOpacity
                onPress={() => {
                  Alert.alert('Kitchen Slip', `Slip #${completedBill.daily_order_number || completedBill.id} printed to kitchen.`);
                }}
                style={[styles.successActionBtn, { backgroundColor: theme.surfaceSubtle }]}
              >
                <Ionicons name="print" size={18} color={theme.text} />
                <Text style={[styles.successActionText, { color: theme.text }]}>Print Kitchen Ticket</Text>
              </TouchableOpacity>

              <TouchableOpacity
                onPress={() => setCompletedBill(null)}
                style={[styles.nextCustBtn, { backgroundColor: theme.primary }]}
              >
                <Text style={styles.nextCustText}>⚡ Ready for Next Customer</Text>
              </TouchableOpacity>
            </View>
          </View>
        </Modal>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  registerBar: { paddingHorizontal: 16, paddingTop: 8, paddingBottom: 8, borderBottomWidth: 1 },
  registerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  registerTitle: { fontSize: 16, fontWeight: '800' },
  registerSub: { fontSize: 11, marginTop: 2 },
  syncBtn: { width: 30, height: 30, borderRadius: 15, alignItems: 'center', justifyContent: 'center' },
  terminalTabsRow: { flexDirection: 'row', borderRadius: 12, padding: 3 },
  terminalTab: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 7, borderRadius: 10, gap: 5 },
  terminalTabActive: { elevation: 1 },
  terminalTabText: { fontSize: 11, fontWeight: '700' },
  orderSetupBar: { paddingHorizontal: 14, paddingVertical: 8, borderBottomWidth: 1 },
  orderTypePillGroup: { flexDirection: 'row', gap: 6, marginBottom: 6 },
  orderTypePill: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 8, borderRadius: 10, gap: 5 },
  orderTypeText: { fontSize: 12, fontWeight: '700' },
  tableChipsRow: { gap: 6, paddingVertical: 2 },
  tableChip: { paddingHorizontal: 12, paddingVertical: 5, borderRadius: 10 },
  tableChipText: { fontSize: 11, fontWeight: '700' },
  menuPunchSection: { padding: 12 },
  catChipsRow: { gap: 6, marginBottom: 10 },
  catChip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 14 },
  catChipText: { fontSize: 11, fontWeight: '600' },
  dishesGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  dishPunchTile: {
    width: '31%',
    borderRadius: 12,
    padding: 8,
    borderWidth: 1,
    minHeight: 64,
    justifyContent: 'space-between',
  },
  tileHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  tilePrice: { fontSize: 11, fontWeight: '800' },
  tileQtyBadge: { paddingHorizontal: 5, paddingVertical: 1, borderRadius: 6 },
  tileQtyText: { color: '#FFFFFF', fontSize: 10, fontWeight: '800' },
  tileName: { fontSize: 11, fontWeight: '600', marginTop: 4 },
  customTile: { borderStyle: 'dashed', alignItems: 'center', justifyContent: 'center', gap: 4 },
  customTileText: { fontSize: 11, fontWeight: '700' },
  runningBillCard: { margin: 12, borderRadius: 18, borderWidth: 1, padding: 14, elevation: 2 },
  billSlipHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  billSlipTitle: { fontSize: 15, fontWeight: '800' },
  billCountBadge: { fontSize: 11, fontWeight: '700', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  clearBillBtn: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  clearBillText: { color: '#DC2626', fontSize: 12, fontWeight: '700' },
  emptyBillNotice: { paddingVertical: 20, alignItems: 'center', gap: 6 },
  emptyBillText: { fontSize: 12, textAlign: 'center' },
  billItemsList: { marginBottom: 10 },
  billItemRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 8, borderBottomWidth: 1 },
  billItemName: { fontSize: 13, fontWeight: '700' },
  billItemPrice: { fontSize: 11, marginTop: 1 },
  stepperBox: { flexDirection: 'row', alignItems: 'center', borderRadius: 8, paddingHorizontal: 4, height: 26 },
  stepBtn: { width: 20, height: 20, alignItems: 'center', justifyContent: 'center' },
  stepCount: { fontSize: 12, fontWeight: '800', paddingHorizontal: 6 },
  billItemSubtotal: { fontSize: 13, fontWeight: '800', minWidth: 60, textAlign: 'right' },
  adjustmentsBox: { padding: 10, borderRadius: 12, marginBottom: 10 },
  adjRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  adjLabel: { fontSize: 11, fontWeight: '600', minWidth: 65 },
  pillsRow: { flexDirection: 'row', gap: 6 },
  adjPill: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  adjPillText: { fontSize: 11, fontWeight: '700' },
  totalsSection: { paddingVertical: 6, gap: 4 },
  totalLine: { flexDirection: 'row', justifyContent: 'space-between' },
  grandLine: { borderTopWidth: 1, paddingTop: 8, marginTop: 6 },
  grandText: { fontSize: 15, fontWeight: '800' },
  grandAmount: { fontSize: 18, fontWeight: '900' },
  payMethodsRow: { flexDirection: 'row', gap: 8, marginVertical: 10 },
  payMethodBtn: { flex: 1, borderWidth: 1, borderRadius: 12, paddingVertical: 10, alignItems: 'center', gap: 4 },
  payMethodText: { fontSize: 11, fontWeight: '700' },
  tenderBox: { borderWidth: 1, borderRadius: 14, padding: 12, marginBottom: 10 },
  tenderInput: { height: 36, backgroundColor: '#FFFFFF', borderRadius: 8, borderWidth: 1, borderColor: '#86EFAC', paddingHorizontal: 10, fontSize: 13, fontWeight: '700', minWidth: 130, textAlign: 'right' },
  noteSuggestionsRow: { flexDirection: 'row', gap: 6, marginTop: 8, flexWrap: 'wrap' },
  noteSuggestionBtn: { backgroundColor: '#DCFCE7', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8 },
  noteSuggestionText: { fontSize: 10, fontWeight: '700', color: '#166534' },
  changeDueRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 10, paddingTop: 8, borderTopWidth: 1, borderTopColor: '#BBF7D0' },
  changeDueLabel: { fontSize: 12, fontWeight: '800', color: '#166534' },
  changeDueAmount: { fontSize: 16, fontWeight: '900', color: '#15803D' },
  optionalCustomerBox: { marginTop: 6, gap: 4 },
  phoneInput: { height: 38, borderWidth: 1, borderRadius: 10, paddingHorizontal: 10, fontSize: 12 },
  searchBox: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, height: 38, borderRadius: 12, gap: 6 },
  searchInput: { flex: 1, fontSize: 13 },
  pastBillCard: { padding: 12, borderRadius: 14, borderWidth: 1, marginBottom: 10 },
  pastBillTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  pastBillNumber: { fontSize: 14, fontWeight: '800' },
  pastBillType: { fontSize: 10, fontWeight: '700', paddingHorizontal: 5, paddingVertical: 1, borderRadius: 4 },
  voidBadgeText: { fontSize: 10, fontWeight: '800', color: '#EF4444', marginLeft: 4 },
  pastBillTime: { fontSize: 11, marginTop: 2 },
  pastBillTotal: { fontSize: 15, fontWeight: '800' },
  pastBillItems: { fontSize: 11, marginVertical: 6 },
  pastBillActions: { flexDirection: 'row', gap: 8, marginTop: 6 },
  pastActionBtn: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 8, gap: 4 },
  pastActionText: { fontSize: 11, fontWeight: '700' },
  keypadContainer: { margin: 14, borderRadius: 20, padding: 16 },
  keypadDisplayBox: { padding: 16, borderRadius: 14, alignItems: 'center', marginBottom: 10 },
  keypadDisplayLabel: { fontSize: 12, fontWeight: '700' },
  keypadDisplayAmount: { fontSize: 28, fontWeight: '900', marginTop: 4 },
  tagPill: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 12, marginRight: 6 },
  tagPillText: { fontSize: 11, fontWeight: '700' },
  numpadGrid: { gap: 8 },
  numpadRow: { flexDirection: 'row', gap: 8 },
  numKey: { flex: 1, height: 52, borderRadius: 12, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
  numKeyText: { fontSize: 20, fontWeight: '800' },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', padding: 20 },
  customItemDialog: { borderRadius: 20, padding: 20 },
  dialogTitle: { fontSize: 17, fontWeight: '800', marginBottom: 14 },
  inputLabel: { fontSize: 12, fontWeight: '700', marginBottom: 4, marginTop: 6 },
  inputBox: { height: 42, borderWidth: 1, borderRadius: 10, paddingHorizontal: 10, fontSize: 13, marginBottom: 8 },
  cancelBtn: { flex: 1, paddingVertical: 10, borderRadius: 10, borderWidth: 1, alignItems: 'center' },
  cancelBtnText: { fontSize: 13, fontWeight: '600' },
  saveBtn: { flex: 1, paddingVertical: 10, borderRadius: 10, alignItems: 'center' },
  saveBtnText: { color: '#FFFFFF', fontSize: 13, fontWeight: '700' },
  successSlipCard: { borderRadius: 24, padding: 24, alignItems: 'center', margin: 10 },
  successCheckCircle: { width: 64, height: 64, borderRadius: 32, backgroundColor: '#DCFCE7', alignItems: 'center', justifyContent: 'center', marginBottom: 12 },
  billPunchedTitle: { fontSize: 18, fontWeight: '900', textAlign: 'center' },
  billPunchedSub: { fontSize: 12, textAlign: 'center', marginTop: 4, marginBottom: 14 },
  changeDueBanner: { backgroundColor: '#DCFCE7', borderRadius: 12, padding: 12, width: '100%', alignItems: 'center', marginBottom: 14 },
  changeDueBannerLabel: { fontSize: 11, fontWeight: '800', color: '#166534' },
  changeDueBannerAmount: { fontSize: 22, fontWeight: '900', color: '#15803D', marginTop: 2 },
  successActionBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 12, paddingHorizontal: 16, borderRadius: 12, width: '100%', gap: 8, marginBottom: 8 },
  successActionText: { color: '#FFFFFF', fontSize: 13, fontWeight: '700' },
  nextCustBtn: { width: '100%', paddingVertical: 14, borderRadius: 14, alignItems: 'center', marginTop: 6 },
  nextCustText: { color: '#FFFFFF', fontSize: 14, fontWeight: '800' },
});
