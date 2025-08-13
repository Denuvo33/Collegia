<script setup lang="ts">

function loginWithGoogle() {
    window.location.href = '/auth/google';
}
</script>

<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="bg-white p-8 rounded shadow-md text-center">
            <h1 class="text-2xl font-bold mb-4 text-black">Login</h1>
            <button
                @click="loginWithGoogle"
                class="flex items-center justify-center gap-2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
            >
                <img
                    src="https://www.svgrepo.com/show/355037/google.svg"
                    alt="Google"
                    class="w-5 h-5"
                />
                Sign in with Google
            </button>
        </div>
    </div>
</template>
