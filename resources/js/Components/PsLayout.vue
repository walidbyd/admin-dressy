<template>
    
    <Head>
        <!-- Page Icon -->
        <link v-if="$page.props.favIcon" rel="icon" type="image/svg+xml"
            :href="$page.props.uploadUrl + '/' + $page.props.favIcon.img_path" />            
    </Head>

    <div class="flex flex-row" :dir="languageStore.getLanguageDir()">

        <!-- right -->
        <div class="flex flex-col flex-grow w-full dark:bg-secondaryDark-black dark:text-textLight">

            <!-- content -->
            <div @click="clickOutsideSidebar" 
                :style="[!$page.props.project.ps_license_code ? 'margin-top: 24px !important;' : '']"
                :class="{ 'xl:ms-76 ms-0': sideMenuStore.isFullSideMenu, 'ms-0 xl:ms-20': !sideMenuStore.isFullSideMenu }"
                class="h-screen px-4 overflow-x-hidden overflow-y-auto transition-all pt-18 pb-18 duration-600 scroll-smooth">
                 
                    <!-- Version Update Notificaiton Card -->
                    <notification-card 
                        v-if="appInfoStore.isNewVersionAvailable"
                        :title="$t('core_be__version_update_noti_title')"
                        :description="appInfoStore.updateDescription ?? $t('core_be__version_update_noti_desc')"
                        icon="hond"       
                        @dismiss="($value) => appInfoStore.isNewVersionAvailable = $value" 
                        class="z-10"                 
                    >
                        <template v-slot:action>
                            <ps-button class="mt-6" rounded="rounded">
                                <a :href="route('NextLaravelUpdater::updateV3')">
                                    {{ $t("btn_update") }}
                                </a>
                            </ps-button>
                        </template>
                    </notification-card>                     

                    <!-- Content Slot -->
                    <slot />
                    
            </div>

        </div>

        <div class="fixed" :style="[!$page.props.project.ps_license_code ? 'top: 24px !important;' : '']" >
            <title-bar />
        </div>

        <!-- left -->
        <div class="fixed flex min-h-screen antialiased ltr:left-0 rtl:right-0"
            :style="[!$page.props.project.ps_license_code ? 'top: 24px !important;' : '']">
            <sidebar-menu />
        </div>

    </div>
    

</template>

<script setup>

import { onMounted, ref } from "vue";
import { Head, Link, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3'
import TitleBar from "@/Components/Layouts/TitleBar/TitleBar.vue";
import SidebarMenu from "@/Components/Layouts/Sidebar/SidebarMenu.vue";
import PsButton from "@/Components/Core/Buttons/PsButton.vue";
import NotificationCard from "@/Components/Layouts/Notification/NotificationCard.vue";
import { useAppInfoStore } from "@/store/AppInfo/AppInfoStore";
import { useThemeStore } from "../store/Utilities/ThemeStore";
import { useLanguageStore } from "../store/Localization/LanguageStore";
import { useSideMenuStore } from "../store/Menu/SideMenuStore";
import { useNotificationStore } from "../store/Notification/NotificationStore";

// Assigning $page.props to a local constant
const { props } = usePage();

onMounted(async () => {

    // Hide The Initial Loading
    var loading = document.getElementById("home_loading__container");
    loading.style.display = "none";
  
    themeStore.initDarkMode();    
    languageStore.initActiveLangauge();
    
});

const notificationStore = useNotificationStore();
notificationStore.initFirebase(props.firebaseConfig);

// Init Stores
const themeStore = useThemeStore();
const languageStore = useLanguageStore();
const sideMenuStore = useSideMenuStore();
const appInfoStore = useAppInfoStore();

// Main Actions

// Init Route and Menu Group
const currentRouteArr = usePage().props.currentRoute;
const currentRoute = currentRouteArr.split(".")[0];
const menugroup = usePage().props.menuGroups;
sideMenuStore.selectActiveMenu(currentRoute, menugroup);
    
appInfoStore.checkNewVersionAvailable(props.builderAppInfo, props.checkVersionUpdate);

function clickOutsideSidebar() {
    sideMenuStore.setSideMenuOpenFlag(false);
}
 
router.on('start', (_) => {
    sideMenuStore.setSideMenuOpenFlag(false);
})

</script>


