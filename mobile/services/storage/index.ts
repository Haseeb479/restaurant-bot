import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const TOKEN_KEY = 'foodio_owner_token';
const RESTAURANT_KEY = 'foodio_restaurant_profile';

let memoryToken: string | null = null;
let memoryProfile: any = null;

export const StorageService = {
  async setToken(token: string): Promise<void> {
    memoryToken = token;
    try {
      if (Platform.OS !== 'web') {
        await SecureStore.setItemAsync(TOKEN_KEY, token);
      }
    } catch (e) {
      console.warn('SecureStore set error:', e);
    }
  },

  async getToken(): Promise<string | null> {
    if (memoryToken) return memoryToken;
    try {
      if (Platform.OS !== 'web') {
        memoryToken = await SecureStore.getItemAsync(TOKEN_KEY);
      }
      return memoryToken;
    } catch (e) {
      return null;
    }
  },

  async setRestaurant(restaurant: any): Promise<void> {
    memoryProfile = restaurant;
    try {
      if (Platform.OS !== 'web') {
        await SecureStore.setItemAsync(RESTAURANT_KEY, JSON.stringify(restaurant));
      }
    } catch (e) {}
  },

  async getRestaurant(): Promise<any | null> {
    if (memoryProfile) return memoryProfile;
    try {
      if (Platform.OS !== 'web') {
        const raw = await SecureStore.getItemAsync(RESTAURANT_KEY);
        if (raw) memoryProfile = JSON.parse(raw);
      }
      return memoryProfile;
    } catch (e) {
      return null;
    }
  },

  async clear(): Promise<void> {
    memoryToken = null;
    memoryProfile = null;
    try {
      if (Platform.OS !== 'web') {
        await SecureStore.deleteItemAsync(TOKEN_KEY);
        await SecureStore.deleteItemAsync(RESTAURANT_KEY);
      }
    } catch (e) {}
  },
};
