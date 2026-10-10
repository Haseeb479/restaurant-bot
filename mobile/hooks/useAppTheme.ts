import { useColorScheme as useRNColorScheme } from 'react-native';
import { Colors } from '../constants/theme';

export function useAppTheme() {
  const scheme = useRNColorScheme();
  const activeScheme = scheme === 'dark' ? 'dark' : 'light';
  return {
    isDark: activeScheme === 'dark',
    theme: Colors[activeScheme],
    scheme: activeScheme,
  };
}
