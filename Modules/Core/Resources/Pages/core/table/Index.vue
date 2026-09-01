<template>
    <Head :title="$t('table_setting_group')" />
    <ps-layout>
        <div class="min-h-screen">
            <!-- breadcrumb start -->
            <ps-breadcrumb-2 :items="breadcrumb" class="mb-4 sm:mb-0" />
            <!-- breadcrumb end -->

            <div class="mt-4 sm:mt-6 lg:mt-10 mb-4 lg:mb-6 flex flex-col sm:flex-row justify-between">
                <div class="flex flex-row justify-between mb-4 sm:mb-0">
                    <ps-button @click="sortingClicked" colors="bg-background dark:bg-backgroundDark" border="border-secondary-200 border dark:border-secondary-100" shadow="shadow-sm" focus="focus:outline-none" hover="hover:outline-none hover:bg-secondary-200">
                        <ps-icon name="sort" class="cursor-pointer me-2"  />
                        <ps-label textColor="text-secondary-800 dark:text-secondary-100 text-sm">{{ $t('core__be_sort_by_name') }}</ps-label>
                    </ps-button>
                    <div class='flex sm:hidden '>
                        <ps-dropdown align="" class=' w-40 h-10' >
                            <template #select>
                                <ps-dropdown-select class="w-40" v-if="usedTypeSearch == 99" placeholder="Choose Table Used Type" :showCenter="true" selectedValue="Null (temp)" />
                                <ps-dropdown-select class="w-40" v-else placeholder="Choose Table Used Type" :showCenter="true" :selectedValue="(usedTypeSearch== '')? '' : tableUsedTypes.filter(table_used_type=>table_used_type.id == usedTypeSearch )[0].name"/>
                            </template>
                            <template #list >
                                <div
                                    class="rounded-md shadow-xs w-40 " >
                                    <div class="pt-2">
                                        <div v-if="tableUsedTypes.length == null">
                                            <ps-label class='p-2 flex' @click="route('core_key_type.index')">Create new currency</ps-label >
                                        </div>
                                        <div v-else>
                                            <div v-for="tableUsedType in tableUsedTypes"
                                                    :key="tableUsedType.id"
                                                    class="w-40 flex py-4 px-2 hover:bg-primary-000 dark:hover:bg-primary-900 cursor-pointer items-center"
                                                    @click="usedTypeClicked(tableUsedType.id)" >
                                                <ps-label class="ms-2" :class="tableUsedType.id == usedTypeSearch ? ' font-bold' : ''"  > {{tableUsedType.name}} </ps-label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </ps-dropdown>
                    </div>

                </div>
                <div class="flex flex-row">
                    <div class='sm:flex hidden me-4'>
                        <ps-dropdown align="" class=' w-40 h-10' >
                            <template #select>

                                <ps-dropdown-select class="w-40" placeholder="Choose Table Used Type" :showCenter="true" :selectedValue="(usedTypeSearch== '')? '' : tableUsedTypes.filter(table_used_type=>table_used_type.id == usedTypeSearch )[0].name"/>
                            </template>
                            <template #list >
                                <div
                                    class="rounded-md shadow-xs w-40 " >
                                    <div class="pt-2">
                                        <div v-if="tableUsedTypes.length == null">
                                            <ps-label class='p-2 flex' @click="route('core_key_type.index')">Create new currency</ps-label >
                                        </div>
                                        <div v-else>

                                            <div v-for="tableUsedType in tableUsedTypes"
                                                    :key="tableUsedType.id"
                                                    class="w-40 flex py-4 px-2 hover:bg-primary-000 dark:hover:bg-primary-900 cursor-pointer items-center"
                                                    @click="usedTypeClicked(tableUsedType.id)" >
                                                <ps-label class="ms-2" :class="tableUsedType.id == usedTypeSearch ? ' font-bold' : ''"  > {{tableUsedType.name}} </ps-label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </ps-dropdown>
                    </div>
                    <ps-input-with-right-icon v-model:value="search" class="w-full sm:w-80" rounded="rounded-lg"  :placeholder="$t('core__be_search')" >
                        <template #icon >
                            <ps-icon name="search" class='cursor-pointer'/>
                        </template>
                    </ps-input-with-right-icon>
                </div>

            </div>


            <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-6">
                <div @click="handleDetail(table.id)" v-for="table in tables.data" :key="table.id" class="cursor-pointer border border-secondary-200 shadow-sm  rounded flex flex-row p-4">
                    <div class="pe-4 border-r border-secondary-200 flex items-center justify-center">
                        <ps-icon  name="box" theme="text-secondary-800 dark:text-secondary-50"  />
                    </div>
                    <div class="flex-grow px-4 " >
                        <ps-label  class="text-lg ">{{table.name}}</ps-label>
                        <div class="flex  rtl:space-x-reverse space-x-1 mt-1">
                            <ps-label class="bg-blue-100 dark:bg-primary-800 p-1 rounded text-xs" textColor="text-blue-800 dark:text-blue-100"> {{ $t('core__be_core_fields') }} </ps-label>
                            <ps-label  class="bg-yellow-100 p-1 rounded text-xs"  textColor="text-yellow-800" v-if="table.is_only_for_core_field != 1"> {{ $t('core__be_custom_fields') }} </ps-label>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="Object.keys(tables.data).length > 0" class="mt-6 lg:mt-8 flex items-center justify-end   rtl:space-x-reverse space-x-1">
               <!-- <ps-button @click="handleLoadmore(baseProjectId)" type="button" class="flex flex-wrap items-center">
                            Load Mores
                </ps-button> -->
                <!-- <ps-icon-button @click="handleLoadmore(baseProjectId)" padding="pe-5 " colors=" dark:bg-green-300 text-indigo-500 dark:text-green-700" hover="outline-0" rightIcon="downChervon">Load More</ps-icon-button> -->

                <Link v-for="(link,index) in tables.meta.links" :key="index" :href="link.url ? link.url : ''" class="h-8 rounded bg-white dark:bg-secondaryDark-black">

                    <div class="flex">
                    <ps-button v-if="index == 0"  hover="" focus=""
                            colors="bg-background dark:bg-backgroundDark hover:bg-secondary-100" class="mt-0.5 "
                            padding="py-2 px-2"
                            border="border border-secodnary-200 dark:border-secodnary-100 ">
                            <ps-icon name="doubleArrowLeft" w="16" h="16"  />
                    </ps-button>

                    <ps-button v-else-if="index == Object.keys(tables.meta.links).length -1"
                            colors="bg-background dark:bg-backgroundDark hover:bg-secondary-100" class="mt-0.5 "
                            padding="py-2 px-2"  hover="" focus=""
                            border="border border-secodnary-200  dark:border-secodnary-100">
                            <ps-icon name="doubleArrowRight" w="16" h="16"  />
                    </ps-button>
                    <ps-button v-else rounded="rounded" colors="bg-white" border="border border-gray-200 rounded"
                        :class="link.active ? 'bg-indigo-500 text-primary-500' : 'hover:text-gray-500 dark:bg-backgroundDark hover:bg-gray-200'"
                        padding='py-2 px-4'
                        >
                        {{link.label}}
                    </ps-button>
                    </div>
                </Link>
            </div>
        </div>
        <ps-loading-circle-dialog ref="ps_loading_circle_dialog" />
        <ps-success-dialog ref="ps_success_dialog" />
        <ps-confirm-dialog ref="ps_confirm_dialog" />
        <ps-warning-dialog ref="ps_warning_dialog" />
        <ps-error-dialog ref="ps_error_dialog" />

    </ps-layout>
</template>

<script>
import { defineComponent,watch,ref } from 'vue'
import PsLayout from "@/Components/PsLayout.vue";
import {Head, Link, useForm} from '@inertiajs/vue3';
import moment from 'moment';
import PsBannerIcon from "@/Components/Core/Banners/PsBannerIcon.vue";
import PsInput from "@/Components/Core/Input/PsInput.vue";
import PsLabel from "@/Components/Core/Label/PsLabel.vue";
import PsButton from "@/Components/Core/Buttons/PsButton.vue";
import PsTextarea from '@/Components/Core/Textarea/PsTextarea.vue';
import PsLabelHeader4 from "@/Components/Core/Label/PsLabelHeader4.vue";
import PsIcon from "@/Components/Core/Icons/PsIcon.vue";
import PsDangerDialog from "@/Components/Core/Dialog/PsDangerDialog.vue";
import PsErrorDialog from "@/Components/Core/Dialog/PsErrorDialog.vue";
import PsToggle from '@/Components/Core/Toggle/PsToggle.vue';
import PsBreadcrumb2 from "@/Components/Core/Breadcrumbs/PsBreadcrumb2.vue";
import PsCard from "@/Components/Core/Card/PsCard.vue";
import PsInputWithRightIcon from '@/Components/Core/Input/PsInputWithRightIcon.vue';
import PsLoadingCircleDialog from '@/Components/Core/Dialog/PsLoadingCircleDialog.vue';
import PsSuccessDialog from '@/Components/Core/Dialog/PsSuccessDialog.vue';
import PsConfirmDialog from '@/Components/Core/Dialog/PsConfirmDialog.vue';
import PsWarningDialog from '@/Components/Core/Dialog/PsWarningDialog.vue';
import PsDropdown from "@/Components/Core/Dropdown/PsDropdown.vue";
import PsDropdownSelect from "@/Components/Core/Dropdown/PsDropdownSelect.vue";

import { trans } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';

export default defineComponent({
    name: "Index",
    components: {
        Head,
        Link,
        PsInput,
        PsLabel,
        PsErrorDialog,
        PsBannerIcon,
        PsButton,
        PsTextarea,
        PsLabelHeader4,
        PsIcon,
        PsConfirmDialog,
        PsWarningDialog,
        PsDangerDialog,
        PsToggle,
        PsBreadcrumb2,
        PsCard,
        PsInputWithRightIcon,
        PsSuccessDialog,
        PsLoadingCircleDialog,
        PsDropdownSelect,
        PsDropdown,
    },
    layout : PsLayout,

    props: ['project',
        'errors',
        'purchased_code',
        'status',
        'logMessages','needRefresh',
        'hasError',  'can','tableUsedTypes','tableUsedTypeId','status','tables','search','loadMore', 'sorting'],

    setup(props) {
        const ps_danger_dialog = ref();
        // const ps_csv_modal = ref();
        const isSorting = props.sorting == 0 ? ref(false) : ref(true);
        const search = props.search ? ref(props.search) : ref('');
        const loadmore =  props.loadMore == 0 ? ref(false) : ref(true);
        const importFile = ref();
        const ps_loading_circle_dialog = ref();
        const ps_success_dialog = ref();
        const ps_confirm_dialog = ref();
        const ps_warning_dialog = ref();
        const usedTypeSearch = props.tableUsedTypeId ? ref(props.tableUsedTypeId) : ref(99);
        let selectedFile = ref();
        const ps_error_dialog = ref();

        function handleDetail(tableId) {
         this.$inertia.get(route("tables.fields.index",[tableId]),{symbol: localStorage.activeLanguage ? localStorage.activeLanguage : 'en',});
        }

        function sortingClicked(){
            router.get(route('table.index'),{sorting: isSorting.value == false ? 0 : 1,loading : loadmore.value == false ? 0 :1  , search:search.value, tableUsedTypeId:usedTypeSearch.value})
        }

        watch(search,value=>{
            setTimeout(() => {
                router.get(route('table.index'),{sorting: isSorting.value == false ? 0 : 1,loading : loadmore.value == false ? 0 :1  , search:search.value, tableUsedTypeId:usedTypeSearch.value})
            }, 2000);

        })

        function usedTypeClicked(v){
            usedTypeSearch.value = v;
            router.get(route('table.index'),{sorting: isSorting.value == false ? 0 : 1,loading : loadmore.value == false ? 0 :1  , search:search.value, tableUsedTypeId:usedTypeSearch.value})
        }

        function importClicked(){
            importFile.value.click();
        }

        function showErrorDialog(msg){
            ps_error_dialog.value.openModal(trans('ps_error_dialog__error'), msg,trans('core__be_btn_ok'), ()=> {});
        }

        function showSuccessDialog(msg){
            ps_success_dialog.value.openModal(trans('ps_success_dialog__success'), msg,trans('core__be_btn_ok'), ()=> {location.reload(true);});
        }

        return {
            showErrorDialog,
            showSuccessDialog,
            moment: moment,
            usedTypeClicked,
            ps_danger_dialog,
            ps_error_dialog,
            route,
            handleDetail,
            sortingClicked,
            isSorting,
            search,
            importClicked,
            importFile,
            ps_success_dialog,
            ps_loading_circle_dialog,
            ps_confirm_dialog,
            ps_warning_dialog,
            usedTypeSearch,
        }
    },
    
    computed: {
        breadcrumb() {

            return [
                {
                    label: trans('core__be_dashboard_label'),
                    url: route('admin.index')
                },
                {
                    label: trans('table_setting_group'),
                    color: "text-primary-500"
                }
            ]

        }
    },
})
</script>
