@props([
    'colspan' => 1,
    'message' => 'Tidak ada data.',
])

<tr>
    <td colspan="{{ (int) $colspan }}" class="px-5 py-10">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>
    </td>
</tr>
