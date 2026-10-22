import React from 'react';
import { Tabs } from 'expo-router';
import { useAppTheme } from '../../hooks/useAppTheme';
import { Ionicons } from '@expo/vector-icons';
import { Platform } from 'react-native';

export default function AppTabsLayout() {
  const { theme } = useAppTheme();

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarShowLabel: true,
        tabBarStyle: {
          backgroundColor: theme.dockBackground,
          borderTopWidth: 0,
          borderWidth: 1,
          borderColor: 'rgba(255, 255, 255, 0.12)',
          height: Platform.OS === 'ios' ? 76 : 64,
          paddingTop: 8,
          paddingBottom: Platform.OS === 'ios' ? 18 : 8,
          marginHorizontal: 16,
          marginBottom: Platform.OS === 'ios' ? 20 : 12,
          borderRadius: 32,
          position: 'absolute',
          elevation: 12,
          shadowColor: '#000000',
          shadowOffset: { width: 0, height: 8 },
          shadowOpacity: 0.25,
          shadowRadius: 16,
        },
        tabBarActiveTintColor: '#FFFFFF',
        tabBarInactiveTintColor: 'rgba(255, 255, 255, 0.55)',
        tabBarLabelStyle: {
          fontSize: 10,
          fontWeight: '700',
          marginTop: 2,
          letterSpacing: 0.2,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          tabBarIcon: ({ color, size, focused }) => (
            <Ionicons
              name={focused ? 'home' : 'home-outline'}
              size={size - 1}
              color={color}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="orders"
        options={{
          title: 'Orders',
          tabBarIcon: ({ color, size, focused }) => (
            <Ionicons
              name={focused ? 'receipt' : 'receipt-outline'}
              size={size - 1}
              color={color}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="pos"
        options={{
          title: 'POS',
          tabBarIcon: ({ color, size, focused }) => (
            <Ionicons
              name={focused ? 'cart' : 'cart-outline'}
              size={size - 1}
              color={color}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="menu"
        options={{
          href: null, // Accessible from More & settings
        }}
      />
      <Tabs.Screen
        name="delivery"
        options={{
          title: 'Delivery',
          tabBarIcon: ({ color, size, focused }) => (
            <Ionicons
              name={focused ? 'bicycle' : 'bicycle-outline'}
              size={size - 1}
              color={color}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="more"
        options={{
          title: 'More',
          tabBarIcon: ({ color, size, focused }) => (
            <Ionicons
              name={focused ? 'grid' : 'grid-outline'}
              size={size - 1}
              color={color}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="kitchen"
        options={{
          href: null,
        }}
      />
    </Tabs>
  );
}
