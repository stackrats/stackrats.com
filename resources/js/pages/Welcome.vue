<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import StackratsLogo from '@/components/stackrats-logo/StackratsLogo.vue';
import Button from '@/components/ui/button/Button.vue';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: false,
    },
);

const darkMode = ref(true)

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value

    if (darkMode.value) {
        setLocalStorageColorThemeDark()
    } else {
        setLocalStorageColorThemeLight()
    }
}

const setLocalStorageColorThemeDark = () => {
    localStorage.setItem('color-theme', 'dark');
    document.documentElement.classList.add('dark')
}
const setLocalStorageColorThemeLight = () => {
    localStorage.setItem('color-theme', 'light');
    document.documentElement.classList.remove('dark')
}

onMounted(() => {
    const hasColorTheme = ('color-theme' in localStorage)
    const hasColorThemeDark = localStorage.getItem('color-theme') === 'dark'
    const preferedColorThemeIsDark = window.matchMedia('(prefers-color-scheme: dark)').matches

    if (hasColorTheme && hasColorThemeDark) {
        setLocalStorageColorThemeDark()
        darkMode.value = true
    } else if (!hasColorTheme && preferedColorThemeIsDark) {
        setLocalStorageColorThemeDark()
        darkMode.value = true
    }
    else {
        setLocalStorageColorThemeLight()
        darkMode.value = false
    }
})
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-neutral-50 p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-neutral-950"
    >
        <header
            class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>

                <Button 
                    variant="secondary" 
                    @click="toggleDarkMode"
                > 
                    <Icon  v-if="darkMode" name="sun"/>
                    <Icon v-else name="moon"/>
                </Button>
            </nav>
        </header>
               
        <div class="flex items-center justify-center transition-opacity opacity-100 duration-750 grow">
            <main class="flex justify-center">
                <StackratsLogo/>
            </main>
        </div>

        <footer class="py-16 text-center text-sm text-gray-600 dark:text-white/70">
            matt@stackrats.com
        </footer>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
