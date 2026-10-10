export interface RestaurantProfile {
  id: number;
  name: string;
  owner_name: string;
  email: string;
  whatsapp_number: string;
  owner_phone: string;
  city: string;
  address: string;
  is_open: boolean;
  is_active: boolean;
  delivery_charge: number;
  minimum_order: number;
  bot_status: string;
}

export interface LoginResponse {
  success: boolean;
  token: string;
  restaurant: RestaurantProfile;
  message?: string;
}

export interface OrderItemData {
  id: number;
  name: string;
  quantity: number;
  subtotal: number;
  variant_name?: string;
}

export interface OrderData {
  id: number;
  order_number?: string;
  customer_name: string;
  customer_phone: string;
  delivery_address: string;
  delivery_lat?: number;
  delivery_lng?: number;
  subtotal: number;
  delivery_charge: number;
  total: number;
  status: 'pending' | 'confirmed' | 'preparing' | 'ready' | 'out_for_delivery' | 'delivered' | 'cancelled';
  payment_method: string;
  payment_status: string;
  rider_name?: string;
  created_at: string;
  items: OrderItemData[];
}
