import { Stack } from 'expo-router';

export default function CreateExpenseLayout() {
  return (
    <Stack>
      <Stack.Screen
        name="[formId]"
        options={{
          title: 'Créer une note de frais',
          headerShown: false,
        }}
      />
    </Stack>
  );
}
