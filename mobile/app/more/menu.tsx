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
} from 'react-native';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAppTheme } from '../../hooks/useAppTheme';
import { apiClient, apiUpload } from '../../services/api/client';
import { LoadingState, ErrorState } from '../../components/FeedbackStates';
import { AppButton } from '../../components/AppButton';
import { Ionicons } from '@expo/vector-icons';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';

export default function MenuScreen() {
  const { theme } = useAppTheme();
  const queryClient = useQueryClient();

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
      setNewItemCatId(null);
      queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
      queryClient.invalidateQueries({ queryKey: ['pos-catalog'] });
      Alert.alert('Success', 'New menu item created.');
    },
    onError: (err: any) => Alert.alert('Error', err.message || 'Failed to create menu item.'),
  });

  // Pick and upload CSV or spreadsheet
  const handleUploadCsv = async () => {
    try {
      const res = await DocumentPicker.getDocumentAsync({
        type: ['text/csv', 'text/comma-separated-values', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        copyToCacheDirectory: true,
      });

      if (res.canceled || !res.assets || res.assets.length === 0) return;

      const file = res.assets[0];
      const formData = new FormData();
      formData.append('file', {
        uri: file.uri,
        name: file.name,
        type: file.mimeType || 'text/csv',
      } as any);

      setIsUploading(true);
      const uploadRes = await apiUpload('/menu/upload', formData);
      Alert.alert('Import Complete', uploadRes.message || 'Menu items imported successfully!');
      queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
      queryClient.invalidateQueries({ queryKey: ['pos-catalog'] });
    } catch (e: any) {
      Alert.alert('Upload Failed', e.message || 'Could not upload spreadsheet file.');
    } finally {
      setIsUploading(false);
    }
  };

  // Pick and upload Menu Poster Image
  const handleUploadImage = async () => {
    try {
      const res = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        quality: 0.9,
      });

      if (res.canceled || !res.assets || res.assets.length === 0) return;

      const asset = res.assets[0];
      const filename = asset.fileName || 'menu_poster.jpg';
      const formData = new FormData();
      formData.append('file', {
        uri: asset.uri,
        name: filename,
        type: asset.mimeType || 'image/jpeg',
      } as any);

      setIsUploading(true);
      const uploadRes = await apiUpload('/menu/upload', formData);
      Alert.alert('Menu Flyer Saved', uploadRes.message || 'Menu poster image saved for WhatsApp bot!');
      queryClient.invalidateQueries({ queryKey: ['menu-catalog'] });
    } catch (e: any) {
      Alert.alert('Image Upload Failed', e.message || 'Could not upload image.');
    } finally {
      setIsUploading(false);
    }
  };

  if (isLoading && !isRefetching) return <LoadingState message="Loading menu items..." />;
  if (error) return <ErrorState message={error.message} onRetry={() => refetch()} />;

  const categories = data?.categories ?? [];
  const uncategorized = data?.uncategorized ?? [];

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: theme.background }]}
      contentContainerStyle={styles.scrollContent}
      refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => refetch()} />}
    >
      {/* ── Screen Header ── */}
      <View style={styles.header}>
        <Text style={[styles.title, { color: theme.text }]}>Menu Management</Text>
        <Text style={[styles.sub, { color: theme.textMuted }]}>
          Add items, update prices, toggle availability, or upload bulk menu files
        </Text>
      </View>

      {/* ── Action Strip: Upload CSV & Upload Poster Image ── */}
      <View style={styles.uploadStrip}>
        <TouchableOpacity
          onPress={handleUploadCsv}
          disabled={isUploading}
          style={[styles.uploadBtn, { backgroundColor: '#E8F5F2', borderColor: '#064E45' }]}
        >
          {isUploading ? (
            <ActivityIndicator size="small" color="#064E45" />
          ) : (
            <>
              <Ionicons name="document-text" size={20} color="#064E45" />
              <View>
                <Text style={[styles.uploadBtnTitle, { color: '#064E45' }]}>Upload Menu CSV</Text>
                <Text style={styles.uploadBtnSub}>Import items & prices</Text>
              </View>
            </>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          onPress={handleUploadImage}
          disabled={isUploading}
          style={[styles.uploadBtn, { backgroundColor: '#FAF5FF', borderColor: '#A855F7' }]}
        >
          {isUploading ? (
            <ActivityIndicator size="small" color="#A855F7" />
          ) : (
            <>
              <Ionicons name="image" size={20} color="#A855F7" />
              <View>
                <Text style={[styles.uploadBtnTitle, { color: '#A855F7' }]}>Upload Flyer Image</Text>
                <Text style={styles.uploadBtnSub}>Menu card for Bot</Text>
              </View>
            </>
          )}
        </TouchableOpacity>
      </View>

      {/* Quick Add Buttons: Add Item & Add Category */}
      <View style={styles.quickAddRow}>
        <TouchableOpacity
          onPress={() => setIsAddItemOpen(true)}
          style={[styles.actionBtn, { backgroundColor: theme.primary }]}
        >
          <Ionicons name="add-circle" size={18} color="#FFFFFF" />
          <Text style={styles.actionBtnText}>Add Menu Item</Text>
        </TouchableOpacity>

        <TouchableOpacity
          onPress={() => setIsAddCatOpen(true)}
          style={[styles.actionBtn, { backgroundColor: theme.surface, borderColor: theme.border, borderWidth: 1 }]}
        >
          <Ionicons name="folder-outline" size={18} color={theme.text} />
          <Text style={[styles.actionBtnText, { color: theme.text }]}>Add Category</Text>
        </TouchableOpacity>
      </View>

      {/* ── Categories & Items List ── */}
      {categories.map((cat: any) => (
        <View key={cat.id} style={styles.categoryBlock}>
          <View style={styles.catHeader}>
            <Text style={[styles.catTitle, { color: theme.text }]}>{cat.name}</Text>
            <Text style={[styles.catCount, { color: theme.textMuted }]}>
              {cat.menu_items?.length || 0} items
            </Text>
          </View>

          {(cat.menu_items || []).map((item: any) => (
            <View
              key={item.id}
              style={[styles.itemCard, { backgroundColor: theme.surface, borderColor: theme.border }]}
            >
              <View style={styles.itemInfo}>
                <Text style={[styles.itemName, { color: theme.text }]}>{item.name}</Text>
                <TouchableOpacity
                  onPress={() => {
                    setEditingItem(item);
                    setEditName(item.name);
                    setEditPrice(String(item.price));
                  }}
                  style={styles.priceRow}
                >
                  <Text style={[styles.itemPrice, { color: theme.primary }]}>
                    Rs. {Number(item.price).toLocaleString()}
                  </Text>
                  <Ionicons name="pencil" size={14} color={theme.textMuted} />
                </TouchableOpacity>
              </View>

              <View style={styles.actionsCol}>
                <View style={styles.switchCol}>
                  <Text
                    style={[
                      styles.availabilityText,
                      { color: item.is_available ? theme.success : theme.danger },
                    ]}
                  >
                    {item.is_available ? 'In Stock' : 'Sold Out'}
                  </Text>
                  <Switch
                    value={!!item.is_available}
                    onValueChange={() => toggleMutation.mutate(item.id)}
                    trackColor={{ false: theme.border, true: theme.success }}
                    thumbColor="#FFFFFF"
                  />
                </View>

                <TouchableOpacity
                  onPress={() => {
                    Alert.alert(
                      'Delete Item',
                      `Are you sure you want to delete "${item.name}"?`,
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
                  style={styles.deleteBtn}
                >
                  <Ionicons name="trash-outline" size={16} color="#EF4444" />
                </TouchableOpacity>
              </View>
            </View>
          ))}
        </View>
      ))}

      {uncategorized.length > 0 && (
        <View style={styles.categoryBlock}>
          <Text style={[styles.catTitle, { color: theme.text }]}>Other Items</Text>
          {uncategorized.map((item: any) => (
            <View
              key={item.id}
              style={[styles.itemCard, { backgroundColor: theme.surface, borderColor: theme.border }]}
            >
              <View style={styles.itemInfo}>
                <Text style={[styles.itemName, { color: theme.text }]}>{item.name}</Text>
                <Text style={[styles.itemPrice, { color: theme.primary }]}>
                  Rs. {Number(item.price).toLocaleString()}
                </Text>
              </View>
              <Switch
                value={!!item.is_available}
                onValueChange={() => toggleMutation.mutate(item.id)}
                trackColor={{ false: theme.border, true: theme.success }}
              />
            </View>
          ))}
        </View>
      )}

      {/* ── Edit Item Modal ── */}
      {editingItem && (
        <Modal visible={!!editingItem} transparent animationType="fade">
          <View style={styles.modalBg}>
            <View style={[styles.modalCard, { backgroundColor: theme.surface }]}>
              <Text style={[styles.modalTitle, { color: theme.text }]}>Edit Menu Item</Text>

              <Text style={[styles.inputLabel, { color: theme.textMuted }]}>Item Name</Text>
              <TextInput
                value={editName}
                onChangeText={setEditName}
                style={[styles.input, { color: theme.text, borderColor: theme.border }]}
              />

              <Text style={[styles.inputLabel, { color: theme.textMuted }]}>Price (PKR)</Text>
              <TextInput
                value={editPrice}
                onChangeText={setEditPrice}
                keyboardType="numeric"
                style={[styles.input, { color: theme.text, borderColor: theme.border }]}
              />

              <View style={styles.modalBtnRow}>
                <AppButton
                  title="Cancel"
                  variant="secondary"
                  onPress={() => setEditingItem(null)}
                  style={{ flex: 1 }}
                />
                <AppButton
                  title="Save Changes"
                  loading={updateItemMutation.isPending}
                  onPress={() =>
                    updateItemMutation.mutate({
                      itemId: editingItem.id,
                      name: editName.trim(),
                      price: Number(editPrice),
                    })
                  }
                  style={{ flex: 1 }}
                />
              </View>
            </View>
          </View>
        </Modal>
      )}

      {/* ── Add Item Modal ── */}
      <Modal visible={isAddItemOpen} transparent animationType="slide">
        <View style={styles.modalBg}>
          <View style={[styles.modalCard, { backgroundColor: theme.surface }]}>
            <Text style={[styles.modalTitle, { color: theme.text }]}>Add New Menu Item</Text>

            <TextInput
              placeholder="Dish Name (e.g. Zinger Burger) *"
              placeholderTextColor={theme.textMuted}
              value={newItemName}
              onChangeText={setNewItemName}
              style={[styles.input, { color: theme.text, borderColor: theme.border }]}
            />

            <TextInput
              placeholder="Price in PKR (e.g. 450) *"
              placeholderTextColor={theme.textMuted}
              value={newItemPrice}
              onChangeText={setNewItemPrice}
              keyboardType="numeric"
              style={[styles.input, { color: theme.text, borderColor: theme.border }]}
            />

            {/* Category selection */}
            <Text style={[styles.inputLabel, { color: theme.textMuted }]}>Select Category</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 14 }}>
              {categories.map((c: any) => {
                const active = newItemCatId === c.id;
                return (
                  <TouchableOpacity
                    key={c.id}
                    onPress={() => setNewItemCatId(c.id)}
                    style={[
                      styles.catSelectPill,
                      active ? { backgroundColor: theme.primary } : { backgroundColor: theme.surfaceSubtle },
                    ]}
                  >
                    <Text style={{ color: active ? '#FFFFFF' : theme.text, fontSize: 12, fontWeight: '700' }}>
                      {c.name}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>

            <TextInput
              placeholder="Description (optional)"
              placeholderTextColor={theme.textMuted}
              value={newItemDesc}
              onChangeText={setNewItemDesc}
              style={[styles.input, { color: theme.text, borderColor: theme.border }]}
            />

            <View style={styles.modalBtnRow}>
              <AppButton
                title="Cancel"
                variant="secondary"
                onPress={() => setIsAddItemOpen(false)}
                style={{ flex: 1 }}
              />
              <AppButton
                title="Add Item"
                loading={createItemMutation.isPending}
                onPress={() => {
                  if (!newItemName.trim() || !newItemPrice.trim()) {
                    Alert.alert('Required', 'Please enter item name and price.');
                    return;
                  }
                  createItemMutation.mutate({
                    name: newItemName.trim(),
                    price: Number(newItemPrice),
                    category_id: newItemCatId,
                    description: newItemDesc.trim() || null,
                  });
                }}
                style={{ flex: 1 }}
              />
            </View>
          </View>
        </View>
      </Modal>

      {/* ── Add Category Modal ── */}
      <Modal visible={isAddCatOpen} transparent animationType="fade">
        <View style={styles.modalBg}>
          <View style={[styles.modalCard, { backgroundColor: theme.surface }]}>
            <Text style={[styles.modalTitle, { color: theme.text }]}>Add New Category</Text>

            <TextInput
              placeholder="Category Name (e.g. Burgers, Drinks) *"
              placeholderTextColor={theme.textMuted}
              value={newCatName}
              onChangeText={setNewCatName}
              style={[styles.input, { color: theme.text, borderColor: theme.border }]}
            />

            <View style={styles.modalBtnRow}>
              <AppButton
                title="Cancel"
                variant="secondary"
                onPress={() => setIsAddCatOpen(false)}
                style={{ flex: 1 }}
              />
              <AppButton
                title="Save Category"
                loading={createCategoryMutation.isPending}
                onPress={() => {
                  if (!newCatName.trim()) {
                    Alert.alert('Required', 'Category name is required.');
                    return;
                  }
                  createCategoryMutation.mutate(newCatName.trim());
                }}
                style={{ flex: 1 }}
              />
            </View>
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollContent: { padding: 16, paddingBottom: 60 },
  header: { marginBottom: 14 },
  title: { fontSize: 22, fontWeight: '800' },
  sub: { fontSize: 13, marginTop: 4 },
  uploadStrip: { flexDirection: 'row', gap: 10, marginBottom: 12 },
  uploadBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 14,
    borderWidth: 1.5,
    borderStyle: 'dashed',
    gap: 10,
  },
  uploadBtnTitle: { fontSize: 13, fontWeight: '800' },
  uploadBtnSub: { fontSize: 11, color: '#64748B', marginTop: 1 },
  quickAddRow: { flexDirection: 'row', gap: 10, marginBottom: 20 },
  actionBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    height: 44,
    borderRadius: 12,
    gap: 6,
  },
  actionBtnText: { color: '#FFFFFF', fontSize: 13, fontWeight: '700' },
  categoryBlock: { marginBottom: 18 },
  catHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  catTitle: { fontSize: 16, fontWeight: '800' },
  catCount: { fontSize: 12 },
  itemCard: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 12,
    borderRadius: 14,
    borderWidth: 1,
    marginBottom: 8,
  },
  itemInfo: { flex: 1 },
  itemName: { fontSize: 14, fontWeight: '700', marginBottom: 4 },
  priceRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  itemPrice: { fontSize: 13, fontWeight: '800' },
  actionsCol: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  switchCol: { alignItems: 'flex-end', gap: 2 },
  availabilityText: { fontSize: 10, fontWeight: '700' },
  deleteBtn: { padding: 6 },
  catSelectPill: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 14, marginRight: 6 },
  modalBg: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', padding: 20 },
  modalCard: { padding: 20, borderRadius: 18, elevation: 4 },
  modalTitle: { fontSize: 18, fontWeight: '800', marginBottom: 14 },
  inputLabel: { fontSize: 12, fontWeight: '600', marginBottom: 6 },
  input: {
    height: 44,
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 12,
    fontSize: 14,
    marginBottom: 12,
  },
  modalBtnRow: { flexDirection: 'row', gap: 10, marginTop: 6 },
});
