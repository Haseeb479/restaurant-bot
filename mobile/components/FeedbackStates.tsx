import React from 'react';
import { View, Text, StyleSheet, ActivityIndicator, useColorScheme } from 'react-native';
import { useAppTheme } from '../hooks/useAppTheme';
import { AppButton } from './AppButton';

export const LoadingState: React.FC<{ message?: string }> = ({ message = 'Loading...' }) => {
  const { theme } = useAppTheme();

  return (
    <View style={styles.center}>
      <ActivityIndicator size="large" color={theme.primary} />
      <Text style={[styles.text, { color: theme.textMuted }]}>{message}</Text>
    </View>
  );
};

export const EmptyState: React.FC<{
  title: string;
  description?: string;
  actionTitle?: string;
  onAction?: () => void;
}> = ({ title, description, actionTitle, onAction }) => {
  const { theme } = useAppTheme();

  return (
    <View style={styles.center}>
      <Text style={[styles.title, { color: theme.text }]}>{title}</Text>
      {description ? (
        <Text style={[styles.desc, { color: theme.textMuted }]}>{description}</Text>
      ) : null}
      {actionTitle && onAction ? (
        <AppButton title={actionTitle} onPress={onAction} style={styles.actionBtn} />
      ) : null}
    </View>
  );
};

export const ErrorState: React.FC<{
  message?: string;
  onRetry?: () => void;
}> = ({ message = 'Something went wrong.', onRetry }) => {
  const { theme } = useAppTheme();

  return (
    <View style={styles.center}>
      <Text style={[styles.title, { color: theme.danger }]}>Error</Text>
      <Text style={[styles.desc, { color: theme.textMuted }]}>{message}</Text>
      {onRetry ? (
        <AppButton title="Try Again" onPress={onRetry} variant="secondary" style={styles.actionBtn} />
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  title: {
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 6,
    textAlign: 'center',
  },
  desc: {
    fontSize: 14,
    textAlign: 'center',
    marginBottom: 16,
  },
  text: {
    fontSize: 14,
    marginTop: 12,
  },
  actionBtn: {
    marginTop: 8,
    minWidth: 140,
  },
});
