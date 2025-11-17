// Android-specific implementation using MaterialIcons

import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { SymbolWeight, SymbolViewProps } from 'expo-symbols';
import { ComponentProps } from 'react';
import { OpaqueColorValue, type StyleProp, type TextStyle } from 'react-native';

type IconMapping = Record<SymbolViewProps['name'], ComponentProps<typeof MaterialIcons>['name']>;
type IconSymbolName = keyof typeof MAPPING;

/**
 * Add your SF Symbols to Material Icons mappings here.
 * - see Material Icons in the [Icons Directory](https://icons.expo.fyi).
 * - see SF Symbols in the [SF Symbols](https://developer.apple.com/sf-symbols/) app.
 */
const MAPPING = {
  // Original mappings
  'house.fill': 'home',
  'paperplane.fill': 'send',
  'chevron.left.forwardslash.chevron.right': 'code',
  'chevron.right': 'chevron-right',

  // SnapFrais app icons
  'list.bullet.clipboard': 'assignment',
  'checkmark.seal': 'verified',
  'plus': 'add',
  'calendar': 'calendar-today',
  'tag': 'label',
  'xmark.circle.fill': 'cancel',
  'pencil': 'edit',
  'checkmark.circle.fill': 'check-circle',
  'clock.fill': 'schedule',
  'storefront': 'store',
  'creditcard': 'credit-card',
  'exclamationmark.triangle.fill': 'warning',
  'fork.knife': 'restaurant',
  'car.fill': 'directions-car',
  'bed.double.fill': 'hotel',
  'cart.fill': 'shopping-cart',
  'ellipsis.circle.fill': 'more-horiz',
  'camera.fill': 'camera-alt',
  'photo.fill': 'photo',
  'checkmark.circle': 'check-circle-outline',
  'checkmark': 'check',
  'xmark': 'close',
  'magnifyingglass': 'search',
  'line.3.horizontal.decrease.circle': 'filter-list',
  'doc.text': 'description',
  'eye': 'visibility',
  'person.fill': 'person',
  'lock.fill': 'lock',
  'eye.slash': 'visibility-off',
  'arrow.right': 'arrow-forward',
  'info.circle': 'info',
  'arrow.right.square': 'logout',
  'building.2': 'business',
} as IconMapping;

/**
 * An icon component that uses Material Icons on Android.
 * This ensures a consistent look and optimal resource usage.
 * Icon `name`s are based on SF Symbols and require manual mapping to Material Icons.
 */
export function IconSymbol({
  name,
  size = 24,
  color,
  style,
}: {
  name: IconSymbolName | string;
  size?: number;
  color: string | OpaqueColorValue;
  style?: StyleProp<TextStyle>;
  weight?: SymbolWeight;
}) {
  const mappedName = MAPPING[name as IconSymbolName];

  if (!mappedName) {
    console.warn(`Icon mapping not found for: ${name}. Using default icon.`);
    return <MaterialIcons color={color} size={size} name="help-outline" style={style} />;
  }

  return <MaterialIcons color={color} size={size} name={mappedName} style={style} />;
}
