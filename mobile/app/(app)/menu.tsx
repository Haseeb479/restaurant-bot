import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Switch,
  RefreshControl,
  Modal,
  TextInput,
  Alert,
  ActivityIndicator,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient, apiUpload } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';

export default function LiveMenuScreen() {
  const { theme } = useAppTheme();
  const queryClient = useQueryClient();

  // Selected Category Filter
  const [selectedCategory, setSelectedCategory] = useState<number | 'all'>('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Edit item state
  const [editingItem, setEditingItem] = useState<any | null>(null);
  const [editPrice, setEditPrice] = useState('');
  const [editName, setEditName] = useState('');

  // Add item modal state
  const [isAddItemOpen, setIsAddItemOpen] = useState(false);
  const [newItemName, setNewItemName] = useState('');
  const [newItemPrice, setNewItemPrice] = useState('');
  const [newItemCatId, setNewItemCatId] = useState<number | null>(null);
  const [newItemDesc, setNewItemDesc] = useState('');

  // Add category modal state
  const [isAddCatOpen, setIsAddCatOpen] = useState(false);
  const [newCatName, setNewCatName] = useState('');

  // Upload state
  const [isUploading, setIsUploading] = useState(false);

  const { data, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['menu-catalog'],
    queryFn: () => apiClient<any>('/menu'),
  });

  const toggleMutation = useMutation({
    mutationFn: (itemId: number) =>
      apiClient(`/menu/items/${itemId}/toggle`, { method: 'POST' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
      queryClient.invalidateQueries({ queryKey: ['pos-catalog'] });
    },
  });

  const updateItemMutation = useMutation({
    mutationFn: ({ itemId, price, name }: { itemId: number; price: number; name?: string }) =>
      apiClient(`/menu/items/${itemId}`, {
        method: 'PATCH',
        body: JSON.stringify({ price, name }),
      }),
    onSuccess: () => {
      setEditingItem(null);
      queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
      queryClient.invalidateQueries({ queryKey: ['pos-catalog'] });
      Alert.alert('Saved', 'Item details updated.');
    },
    onError: (err: any) => Alert.alert('Error', err.message || 'Failed to update item.'),
  });

  const deleteItemMutation = useMutation({
    mutationFn: (itemId: number) =>
      apiClient(`/menu/items/${itemId}`, { method: 'DELETE' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
      queryClient.invalidateQueries({ queryKey: ['pos-catalog'] });
      Alert.alert('Deleted', 'Item removed from menu.');
    },
    onError: (err: any) => Alert.alert('Error', err.message || 'Failed to delete item.'),
  });

  const createCategoryMutation = useMutation({
    mutationFn: (name: string) =>
      apiClient('/menu/categories', {
        method: 'POST',
        body: JSON.stringify({ name }),
      }),
    onSuccess: () => {
      setIsAddCatOpen(false);
      setNewCatName('');
      queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
      Alert.alert('Success', 'Category added.');
    },
    onError: (err: any) => Alert.alert('Error', err.message || 'Failed to add category.'),
  });

  const createItemMutation = useMutation({
    mutationFn: (payload: any) =>
      apiClient('/menu/items', {
        method: 'POST',
        body: JSON.stringify(payload),
      }),
    onSuccess: () => {
      setIsAddItemOpen(false);
      setNewItemName('');
      setNewItemPrice('');
      setNewItemDesc('');
      queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
      queryClient.invalidateQueries({ queryKey: ['pos-catalog'] });
      Alert.alert('Success', 'Menu item added.');
    },
    onError: (err: any) => Alert.alert('Error', err.message || 'Failed to add item.'),
  });

  // Pick & Upload CSV
  const handleUploadCsv = async () => {
    try {
      const res = await DocumentPicker.getDocumentAsync({
        type: ['text/csv', 'text/comma-separated-values', 'application/vnd.ms-excel'],
        copyToCacheDirectory: true,
      });

      if (res.canceled || !res.assets || res.assets.length === 0) return;

      const file = res.assets[0];
      setIsUploading(true);
      const formData = new FormData();
      formData.append('file', {
        uri: file.uri,
        name: file.name,
        type: file.mimeType || 'text/csv',
      } as any);

      const uploadRes = await apiUpload<any>('/menu/upload', formData);
      setIsUploading(false);

      if (uploadRes.success) {
        queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
        queryClient.invalidateQueries({ queryKey: ['pos-catalog'] });
        Alert.alert('CSV Imported', uploadRes.message);
      } else {
        Alert.alert('Upload Failed', uploadRes.message || 'Could not import CSV.');
      }
    } catch (e: any) {
      setIsUploading(false);
      Alert.alert('Error', e.message || 'CSV upload failed.');
    }
  };

  // Pick & Upload Menu Photo (Camera or Gallery)
  const handleUploadMenuImage = async () => {
    try {
      Alert.alert(
        'Upload Menu Photo',
        'Choose source for menu photo:',
        [
          {
            text: 'Take Photo (Camera)',
            onPress: async () => {
              const perm = await ImagePicker.requestCameraPermissionsAsync();
              if (!perm.granted) {
                Alert.alert('Permission Denied', 'Camera permission required.');
                return;
              }
              const res = await ImagePicker.launchCameraAsync({ quality: 0.8 });
              if (!res.canceled && res.assets[0]) {
                await processImageUpload(res.assets[0]);
              }
            },
          },
          {
            text: 'Choose from Gallery',
            onPress: async () => {
              const res = await ImagePicker.launchImageLibraryAsync({ quality: 0.8 });
              if (!res.canceled && res.assets[0]) {
                await processImageUpload(res.assets[0]);
              }
            },
          },
          { text: 'Cancel', style: 'cancel' },
        ]
      );
    } catch (e: any) {
      Alert.alert('Error', e.message);
    }
  };

  const processImageUpload = async (asset: any) => {
    setIsUploading(true);
    try {
      const formData = new FormData();
      formData.append('file', {
        uri: asset.uri,
        name: asset.fileName || 'menu_photo.jpg',
        type: asset.mimeType || 'image/jpeg',
      } as any);

      const uploadRes = await apiUpload<any>('/menu/upload', formData);
      setIsUploading(false);
      if (uploadRes.success) {
        queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
        queryClient.invalidateQueries({ queryKey: ['pos-catalog'] });
        Alert.alert('Photo Saved', uploadRes.message);
      } else {
        Alert.alert('Upload Failed', uploadRes.message);
      }
    } catch (err: any) {
      setIsUploading(false);
      Alert.alert('Upload Error', err.message);
    }
  };

  if (isLoading && !isRefetching) {
    return <LoadingState message="Loading Restaurant Menu & 86ing Catalog..." />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => refetch()} />;
  }

  const categories = data?.categories ?? [];
  const uncategorized = data?.uncategorized ?? [];

  const allItems: any[] = [];
  categories.forEach((cat: any) => {
    (cat.menu_items || []).forEach((it: any) => {
      allItems.push({ ...it, category_name: cat.name, cat_id: cat.id });
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

  const totalItems = allItems.length;
  const inStockCount = allItems.filter((i) => i.is_available).length;
  const soldOutCount = totalItems - inStockCount;

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top']}>
      {/* ── Top Header ── */}
      <View style={[styles.headerArea, { backgroundColor: theme.surface, borderBottomColor: theme.border }]}>
        <View style={styles.titleRow}>
          <View>
            <Text style={[styles.headerTitle, { color: theme.text }]}>Menu Management & 86ing</Text>
            <Text style={[styles.headerSub, { color: theme.textMuted }]}>
              {inStockCount} Available • {soldOutCount} Sold Out (Instant WhatsApp Sync)
            </Text>
          </View>
          <TouchableOpacity onPress={() => setIsAddItemOpen(true)} style={[styles.addBtn, { backgroundColor: theme.primary }]}>
            <Ionicons name="add" size={18} color="#FFFFFF" />
            <Text style={styles.addBtnText}>Add Item</Text>
          </TouchableOpacity>
        </View>

        {/* ── Search Bar ── */}
        <View style={styles.searchRow}>
          <View style={[styles.searchBox, { backgroundColor: theme.surfaceSubtle }]}>
            <Ionicons name="search" size={16} color={theme.textMuted} />
            <TextInput
              placeholder="Search dishes to toggle or edit..."
              placeholderTextColor={theme.textMuted}
              value={searchQuery}
              onChangeText={setSearchQuery}
              style={[styles.searchInput, { color: theme.text }]}
            />
            {searchQuery ? (
              <TouchableOpacity onPress={() => setSearchQuery('')}>
                <Ionicons name="close-circle" size={16} color={theme.textMuted} />
              </TouchableOpacity>
            ) : null}
          </View>
        </View>

        {/* ── Quick Upload Action Row (CSV / Image Upload) ── */}
        <View style={styles.uploadRow}>
          <TouchableOpacity
            onPress={handleUploadCsv}
            disabled={isUploading}
            style={[styles.uploadPill, { backgroundColor: theme.primaryLight }]}
          >
            <Ionicons name="document-text" size={15} color={theme.primary} />
            <Text style={[styles.uploadPillText, { color: theme.primary }]}>Upload Menu CSV</Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={handleUploadMenuImage}
            disabled={isUploading}
            style={[styles.uploadPill, { backgroundColor: '#F0FDF4' }]}
          >
            <Ionicons name="camera" size={15} color="#16A34A" />
            <Text style={[styles.uploadPillText, { color: '#16A34A' }]}>Upload Menu Photo</Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setIsAddCatOpen(true)}
            style={[styles.uploadPill, { backgroundColor: theme.surfaceSubtle }]}
          >
            <Ionicons name="folder-outline" size={15} color={theme.text} />
            <Text style={[styles.uploadPillText, { color: theme.text }]}>+ Category</Text>
          </TouchableOpacity>
        </View>

        {/* ── Category Chips ── */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.categoryChipsRow}>
          <TouchableOpacity
            onPress={() => setSelectedCategory('all')}
            style={[
              styles.catChip,
              selectedCategory === 'all'
                ? { backgroundColor: theme.primary }
                : { backgroundColor: theme.surfaceSubtle },
            ]}
          >
            <Text
              style={[
                styles.catChipText,
                { color: selectedCategory === 'all' ? '#FFFFFF' : theme.textMuted },
                selectedCategory === 'all' && { fontWeight: '700' },
              ]}
            >
              All Items ({allItems.length})
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
                <Text
                  style={[
                    styles.catChipText,
                    { color: active ? '#FFFFFF' : theme.textMuted },
                    active && { fontWeight: '700' },
                  ]}
                >
                  {c.name} ({c.menu_items?.length || 0})
                </Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>
      </View>

      {/* ── Items List with 1-Tap 86 Switches ── */}
      <ScrollView
        contentContainerStyle={styles.scrollList}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
      >
        {isUploading && (
          <View style={[styles.uploadingBox, { backgroundColor: theme.primaryLight }]}>
            <ActivityIndicator size="small" color={theme.primary} />
            <Text style={[styles.uploadingText, { color: theme.primary }]}>
              Uploading and analyzing menu file...
            </Text>
          </View>
        )}

        {filteredItems.map((item) => {
          const isAvail = Boolean(item.is_available);
          return (
            <View
              key={item.id}
              style={[
                styles.menuItemCard,
                { backgroundColor: theme.surface, borderColor: theme.border },
                !isAvail && styles.soldOutCard,
              ]}
            >
              <View style={styles.itemInfo}>
                <View style={styles.nameHeaderRow}>
                  <Text style={[styles.itemName, { color: theme.text }, !isAvail && styles.soldOutText]}>
                    {item.name}
                  </Text>
                  <View
                    style={[
                      styles.stockStatusBadge,
                      { backgroundColor: isAvail ? '#DCFCE7' : '#FEE2E2' },
                    ]}
                  >
                    <View
                      style={[
                        styles.stockDot,
                        { backgroundColor: isAvail ? '#16A34A' : '#DC2626' },
                      ]}
                    />
                    <Text
                      style={[
                        styles.stockStatusText,
                        { color: isAvail ? '#16A34A' : '#DC2626' },
                      ]}
                    >
                      {isAvail ? 'Available' : 'Sold Out (86)'}
                    </Text>
                  </View>
                </View>

                <Text style={[styles.itemSub, { color: theme.textMuted }]}>
                  {item.category_name} • Rs. {Number(item.price).toLocaleString()}
                </Text>
                {item.description ? (
                  <Text style={[styles.itemDesc, { color: theme.textMuted }]} numberOfLines={2}>
                    {item.description}
                  </Text>
                ) : null}
              </View>

              {/* Action Buttons: 1-Tap 86 Toggle & Edit */}
              <View style={styles.itemActions}>
                <View style={styles.switchWrapper}>
                  <Text style={[styles.switchLabel, { color: isAvail ? '#16A34A' : '#DC2626' }]}>
                    {isAvail ? 'Live 🟢' : '86ed 🔴'}
                  </Text>
                  <Switch
                    value={isAvail}
                    onValueChange={() => toggleMutation.mutate(item.id)}
                    trackColor={{ false: '#EF4444', true: '#22C55E' }}
                    thumbColor="#FFFFFF"
                  />
                </View>

                <View style={styles.editBtnGroup}>
                  <TouchableOpacity
                    onPress={() => {
                      setEditingItem(item);
                      setEditName(item.name);
                      setEditPrice(String(item.price));
                    }}
                    style={[styles.smallActionBtn, { backgroundColor: theme.surfaceSubtle }]}
                  >
                    <Ionicons name="create-outline" size={16} color={theme.text} />
                  </TouchableOpacity>

                  <TouchableOpacity
                    onPress={() => {
                      Alert.alert(
                        'Delete Item?',
                        `Are you sure you want to remove "${item.name}" from your menu?`,
                        [
                          { text: 'Cancel', style: 'cancel' },
                          {
                            text: 'Delete',
                            style: 'destructive',
                            onPress: () => deleteItemMutation.mutate(item.id),
                          },
                        ]
                      );
                    }}
                    style={[styles.smallActionBtn, { backgroundColor: '#FEE2E2' }]}
                  >
                    <Ionicons name="trash-outline" size={16} color="#DC2626" />
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          );
        })}
      </ScrollView>

      {/* ── Edit Item Modal ── */}
      {editingItem && (
        <Modal visible={!!editingItem} animationType="fade" transparent onRequestClose={() => setEditingItem(null)}>
          <View style={styles.modalBackdrop}>
            <View style={[styles.editDialog, { backgroundColor: theme.surface }]}>
              <Text style={[styles.dialogTitle, { color: theme.text }]}>Edit Dish Details</Text>

              <Text style={[styles.inputLabel, { color: theme.text }]}>Item Name</Text>
              <TextInput
                value={editName}
                onChangeText={setEditName}
                style={[styles.inputBox, { color: theme.text, borderColor: theme.border }]}
              />

              <Text style={[styles.inputLabel, { color: theme.text }]}>Price (Rs.)</Text>
              <TextInput
                value={editPrice}
                onChangeText={setEditPrice}
                keyboardType="numeric"
                style={[styles.inputBox, { color: theme.text, borderColor: theme.border }]}
              />

              <View style={styles.dialogActions}>
                <TouchableOpacity
                  onPress={() => setEditingItem(null)}
                  style={[styles.dialogCancelBtn, { borderColor: theme.border }]}
                >
                  <Text style={[styles.dialogCancelText, { color: theme.text }]}>Cancel</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  onPress={() => {
                    const price = Number(editPrice);
                    if (isNaN(price) || price < 0) {
                      Alert.alert('Invalid Price', 'Please enter a valid price.');
                      return;
                    }
                    updateItemMutation.mutate({
                      itemId: editingItem.id,
                      price,
                      name: editName.trim(),
                    });
                  }}
                  style={[styles.dialogSaveBtn, { backgroundColor: theme.primary }]}
                >
                  <Text style={styles.dialogSaveText}>Save Changes</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>
      )}

      {/* ── Add Item Modal ── */}
      <Modal visible={isAddItemOpen} animationType="slide" transparent onRequestClose={() => setIsAddItemOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.sheetModal, { backgroundColor: theme.surface }]}>
            <View style={styles.sheetHeader}>
              <Text style={[styles.dialogTitle, { color: theme.text }]}>Add New Menu Item</Text>
              <TouchableOpacity onPress={() => setIsAddItemOpen(false)}>
                <Ionicons name="close-circle" size={26} color={theme.textMuted} />
              </TouchableOpacity>
            </View>

            <ScrollView style={{ maxHeight: 380 }}>
              <Text style={[styles.inputLabel, { color: theme.text }]}>Dish / Item Name *</Text>
              <TextInput
                placeholder="e.g. Zinger Supreme Burger"
                placeholderTextColor={theme.textMuted}
                value={newItemName}
                onChangeText={setNewItemName}
                style={[styles.inputBox, { color: theme.text, borderColor: theme.border }]}
              />

              <Text style={[styles.inputLabel, { color: theme.text }]}>Price (Rs.) *</Text>
              <TextInput
                placeholder="e.g. 650"
                placeholderTextColor={theme.textMuted}
                value={newItemPrice}
                onChangeText={setNewItemPrice}
                keyboardType="numeric"
                style={[styles.inputBox, { color: theme.text, borderColor: theme.border }]}
              />

              <Text style={[styles.inputLabel, { color: theme.text }]}>Category</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 12 }}>
                {categories.map((c: any) => {
                  const active = newItemCatId === c.id;
                  return (
                    <TouchableOpacity
                      key={c.id}
                      onPress={() => setNewItemCatId(c.id)}
                      style={[
                        styles.catChoiceChip,
                        { borderColor: active ? theme.primary : theme.border },
                        active && { backgroundColor: theme.primaryLight },
                      ]}
                    >
                      <Text style={[styles.catChoiceText, { color: active ? theme.primary : theme.text }]}>
                        {c.name}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>

              <Text style={[styles.inputLabel, { color: theme.text }]}>Description / Included Items</Text>
              <TextInput
                placeholder="e.g. Crispy fillet, signature sauce, fresh lettuce"
                placeholderTextColor={theme.textMuted}
                value={newItemDesc}
                onChangeText={setNewItemDesc}
                multiline
                numberOfLines={2}
                style={[styles.inputBoxMulti, { color: theme.text, borderColor: theme.border }]}
              />
            </ScrollView>

            <View style={{ marginTop: 14 }}>
              <AppButton
                title="Add to Menu"
                loading={createItemMutation.isPending}
                onPress={() => {
                  if (!newItemName.trim() || !newItemPrice.trim()) {
                    Alert.alert('Required Fields', 'Please enter both item name and price.');
                    return;
                  }
                  createItemMutation.mutate({
                    name: newItemName.trim(),
                    price: Number(newItemPrice),
                    category_id: newItemCatId,
                    description: newItemDesc.trim(),
                    is_available: true,
                  });
                }}
              />
            </View>
          </View>
        </View>
      </Modal>

      {/* ── Add Category Modal ── */}
      <Modal visible={isAddCatOpen} animationType="fade" transparent onRequestClose={() => setIsAddCatOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.editDialog, { backgroundColor: theme.surface }]}>
            <Text style={[styles.dialogTitle, { color: theme.text }]}>Add New Category</Text>

            <Text style={[styles.inputLabel, { color: theme.text }]}>Category Name *</Text>
            <TextInput
              placeholder="e.g. Desserts & Shakes"
              placeholderTextColor={theme.textMuted}
              value={newCatName}
              onChangeText={setNewCatName}
              style={[styles.inputBox, { color: theme.text, borderColor: theme.border }]}
            />

            <View style={styles.dialogActions}>
              <TouchableOpacity
                onPress={() => setIsAddCatOpen(false)}
                style={[styles.dialogCancelBtn, { borderColor: theme.border }]}
              >
                <Text style={[styles.dialogCancelText, { color: theme.text }]}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                onPress={() => {
                  if (!newCatName.trim()) {
                    Alert.alert('Category Name Required', 'Please enter a valid category name.');
                    return;
                  }
                  createCategoryMutation.mutate(newCatName.trim());
                }}
                style={[styles.dialogSaveBtn, { backgroundColor: theme.primary }]}
              >
                <Text style={styles.dialogSaveText}>Create</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  headerArea: { paddingHorizontal: 16, paddingTop: 10, paddingBottom: 10, borderBottomWidth: 1 },
  titleRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  headerTitle: { fontSize: 18, fontWeight: '800' },
  headerSub: { fontSize: 12, marginTop: 2 },
  addBtn: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 12, gap: 4 },
  addBtnText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700' },
  searchRow: { marginBottom: 10 },
  searchBox: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, height: 38, borderRadius: 12, gap: 6 },
  searchInput: { flex: 1, fontSize: 13 },
  uploadRow: { flexDirection: 'row', gap: 8, marginBottom: 10 },
  uploadPill: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 12, gap: 5 },
  uploadPillText: { fontSize: 11, fontWeight: '700' },
  categoryChipsRow: { gap: 8, paddingBottom: 2 },
  catChip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 16 },
  catChipText: { fontSize: 12, fontWeight: '600' },
  scrollList: { padding: 14, paddingBottom: 90 },
  uploadingBox: { flexDirection: 'row', alignItems: 'center', padding: 12, borderRadius: 12, marginBottom: 12, gap: 8 },
  uploadingText: { fontSize: 12, fontWeight: '600' },
  menuItemCard: { flexDirection: 'row', padding: 14, borderRadius: 16, borderWidth: 1, marginBottom: 10, elevation: 1 },
  soldOutCard: { opacity: 0.75, backgroundColor: '#FFF5F5' },
  itemInfo: { flex: 1, paddingRight: 10 },
  nameHeaderRow: { flexDirection: 'row', alignItems: 'center', gap: 6, flexWrap: 'wrap' },
  itemName: { fontSize: 14, fontWeight: '700' },
  soldOutText: { textDecorationLine: 'line-through', color: '#DC2626' },
  stockStatusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 8, gap: 4 },
  stockDot: { width: 6, height: 6, borderRadius: 3 },
  stockStatusText: { fontSize: 10, fontWeight: '700' },
  itemSub: { fontSize: 12, marginTop: 4, fontWeight: '600' },
  itemDesc: { fontSize: 11, marginTop: 4 },
  itemActions: { alignItems: 'flex-end', justifyContent: 'space-between' },
  switchWrapper: { alignItems: 'center' },
  switchLabel: { fontSize: 10, fontWeight: '800', marginBottom: 2 },
  editBtnGroup: { flexDirection: 'row', gap: 6, marginTop: 8 },
  smallActionBtn: { width: 32, height: 32, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', padding: 20 },
  editDialog: { borderRadius: 20, padding: 20 },
  sheetModal: { borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20, maxHeight: '85%' },
  sheetHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 },
  dialogTitle: { fontSize: 18, fontWeight: '800', marginBottom: 14 },
  inputLabel: { fontSize: 12, fontWeight: '700', marginBottom: 6, marginTop: 6 },
  inputBox: { height: 42, borderWidth: 1, borderRadius: 10, paddingHorizontal: 10, fontSize: 13, marginBottom: 8 },
  inputBoxMulti: { height: 60, borderWidth: 1, borderRadius: 10, paddingHorizontal: 10, paddingTop: 8, fontSize: 13, marginBottom: 8 },
  catChoiceChip: { borderWidth: 1, borderRadius: 10, paddingHorizontal: 12, paddingVertical: 6, marginRight: 8 },
  catChoiceText: { fontSize: 12, fontWeight: '600' },
  dialogActions: { flexDirection: 'row', gap: 10, marginTop: 14 },
  dialogCancelBtn: { flex: 1, paddingVertical: 10, borderRadius: 10, borderWidth: 1, alignItems: 'center' },
  dialogCancelText: { fontSize: 13, fontWeight: '600' },
  dialogSaveBtn: { flex: 1, paddingVertical: 10, borderRadius: 10, alignItems: 'center' },
  dialogSaveText: { color: '#FFFFFF', fontSize: 13, fontWeight: '700' },
});
