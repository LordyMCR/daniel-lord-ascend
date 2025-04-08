<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    overdueUsers: {
        type: Array,
        required: true,
    },
    message: {
        type: String,
        default: null,
    },
});

const hasOverdueUsers = computed(() => props.overdueUsers.length > 0);

const isSendingBulk = ref(false);

const sendBulkReminders = () => {
    if (isSendingBulk.value) return;

    isSendingBulk.value = true;

    router.post(route('admin.send_bulk_reminders'), {}, {
        onFinish: () => {
            isSendingBulk.value = false;
        },
    });
};
</script>


<template>
    <Head title="Overdue Users" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Overdue Users
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-if="message">
                            <p class="text-gray-600">{{ message }}</p>
                        </div>
                        <div v-else-if="hasOverdueUsers">
                            <div class="mb-4 flex justify-end">
                                <button
                                    @click="sendBulkReminders"
                                    :disabled="isSendingBulk"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isSendingBulk">Sending...</span>
                                    <span v-else>Send Bulk Reminders</span>
                                </button>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Book Title
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Due Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in overdueUsers" :key="user.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ user.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" v-for="borrow in user.borrows" :key="borrow.id">
                                        <div class="text-sm text-gray-900">{{ borrow.book_copy.book.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" v-for="borrow in user.borrows" :key="borrow.id">
                                        <div class="text-sm text-gray-900">{{ new Date(borrow.due_date).toLocaleString(undefined, { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            v-for="borrow in user.borrows"
                                            :key="borrow.id"
                                            @click="$inertia.post(route('admin.send_reminder', borrow.id))"
                                            class="text-indigo-600 hover:text-indigo-900 mr-2"
                                        >
                                            Send Reminder
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else>
                            <p class="text-gray-600">No overdue users found.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
