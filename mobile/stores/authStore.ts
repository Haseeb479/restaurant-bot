import { create } from 'zustand';
import { StorageService } from '../services/storage';
import { apiClient } from '../services/api/client';
import { RestaurantProfile, LoginResponse } from '../types/api';

interface AuthState {
  token: string | null;
  restaurant: RestaurantProfile | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (restaurantIdentifier: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  restoreSession: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
  token: null,
  restaurant: null,
  isLoading: true,
  isAuthenticated: false,

  restoreSession: async () => {
    set({ isLoading: true });
    try {
      const token = await StorageService.getToken();
      if (!token) {
        set({ token: null, restaurant: null, isAuthenticated: false, isLoading: false });
        return;
      }

      // Verify token with backend
      const res = await apiClient<{ success: boolean; restaurant: RestaurantProfile }>('/auth/me');
      if (res.success && res.restaurant) {
        await StorageService.setRestaurant(res.restaurant);
        set({
          token,
          restaurant: res.restaurant,
          isAuthenticated: true,
          isLoading: false,
        });
      } else {
        await StorageService.clear();
        set({ token: null, restaurant: null, isAuthenticated: false, isLoading: false });
      }
    } catch {
      await StorageService.clear();
      set({ token: null, restaurant: null, isAuthenticated: false, isLoading: false });
    }
  },

  login: async (restaurantIdentifier: string, password: string) => {
    const res = await apiClient<LoginResponse>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({
        restaurant_name: restaurantIdentifier,
        password,
      }),
    });

    if (res.success && res.token && res.restaurant) {
      await StorageService.setToken(res.token);
      await StorageService.setRestaurant(res.restaurant);
      set({
        token: res.token,
        restaurant: res.restaurant,
        isAuthenticated: true,
      });
    } else {
      throw new Error(res.message || 'Login failed.');
    }
  },

  logout: async () => {
    try {
      await apiClient('/auth/logout', { method: 'POST' });
    } catch {}
    await StorageService.clear();
    set({ token: null, restaurant: null, isAuthenticated: false });
  },
}));
