<template>
    <div>
        <h1>Reminder: Your Book "{{ bookTitle }}" is Overdue</h1>
        <p>Hello {{ userName }},</p>
        <p>This is a reminder that the following book you borrowed is currently overdue:</p>
        <ul>
            <li><strong>Book Title:</strong> {{ bookTitle }}</li>
            <li><strong>Due Date:</strong> {{ dueDate }}</li>
        </ul>
        <p>Please return the book to the library as soon as possible.</p>
        <p>If you have already returned this book, please disregard this email.</p>
        <p>Regards,<br>The Library Team</p>
    </div>
</template>

<script>
export default {
    props: {
        userName: {
            type: String,
            required: true,
        },
        bookTitle: {
            type: String,
            required: true,
        },
        dueDate: {
            type: String,
            required: true,
        },
    },
};
</script>
