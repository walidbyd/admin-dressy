import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAppInfoStore = defineStore('CoreAppInfoStore', () => {
    const isNewVersionAvailable = ref(false);
    const updateDescription = ref("");

    function checkNewVersionAvailable(appInfo : any, currentVersionInfos : any) {
        if(appInfo != null && appInfo != ''){

            const builderVersioncode = appInfo?.latestVersion?.version_code;
        
            if(currentVersionInfos != null){
                const sourceCode = currentVersionInfos.source_code_version_code == builderVersioncode ? true : false;
                const backendLanguage = currentVersionInfos.backend_language_version_code == builderVersioncode ? true : false;
                const frontendLanguage = currentVersionInfos.frontend_language_version_code == builderVersioncode ? true : false;
                const mobileLanguage = currentVersionInfos.mobile_language_version_code == builderVersioncode ? true : false;
                if(sourceCode 
                    && backendLanguage 
                    && frontendLanguage 
                    && mobileLanguage){
                    isNewVersionAvailable.value = false;
                }else{
                    isNewVersionAvailable.value = true;
                    updateDescription.value = appInfo?.latestVersion?.description;
                }
            }else{
                isNewVersionAvailable.value = true;
            }
        
        }
    }

    return {
        isNewVersionAvailable,
        updateDescription,
        checkNewVersionAvailable,
    };
});


