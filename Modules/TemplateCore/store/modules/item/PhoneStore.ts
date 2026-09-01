import { reactive } from "vue";

import PhoneParameterHolder from "@templateCore/object/holder/PhoneParameterHolder";
import PsApiService from "@templateCore/api/PsApiService";
import Phone from "@templateCore/object/Phone";
import PsResource from "@templateCore/api/common/PsResource";
import { defineStore } from "pinia";
import makeSeparatedStore from "@templateCore/store/modules/core/PsSepetetedStore";

export const usePhoneStore = makeSeparatedStore((key: string) =>
    defineStore(`phoneStore/${key}`, () => {
        const phoneList = reactive<PsResource<Phone[]>>(new PsResource());
        const loading = reactive({
            value: false,
        });

        let limit: number = 10;
        let offset: number = 0;
        const isNoMoreRecord = reactive({
            value: false,
        });

        let id: string = "";
        let phoneparamHolder = reactive<PhoneParameterHolder>(
            new PhoneParameterHolder().getRecentParameterHolder()
        );

        function updatePhoneList(responseData: PsResource<Phone[]>) {
            if (
                phoneList != null &&
                phoneList.data != null &&
                phoneList.data.length > 0 &&
                offset != 0
            ) {
                if (responseData.data != null && responseData.data.length > 0) {
                    if (responseData.data?.length < limit) {
                        isNoMoreRecord.value = true;
                    } else {
                        isNoMoreRecord.value = false;
                    }
                    phoneList.data.push(...responseData.data);
                } else {
                    isNoMoreRecord.value = true;
                }
                phoneList.code = responseData.code;
                phoneList.status = responseData.status;
                phoneList.message = responseData.message;
            } else {
                if (
                    responseData.data?.length < limit ||
                    responseData.data == null
                ) {
                    isNoMoreRecord.value = true;
                } else {
                    isNoMoreRecord.value = false;
                }
                phoneList.data = responseData.data;
                phoneList.code = responseData.code;
                phoneList.status = responseData.status;
                phoneList.message = responseData.message;
            }

            if (phoneList != null && phoneList.data != null) {
                offset = phoneList.data.length;
            }
        }

        function filterPhoneUpdate(
            loginUserId: string,
            holder: PhoneParameterHolder
        ) {
            resetPhoneList(loginUserId, holder);
        }

        async function resetPhoneList(
            loginUserId: string,
            holder: PhoneParameterHolder
        ) {
            offset = 0;

            loading.value = true;

            const responseData = await PsApiService.getPhoneList<Phone>(
                new Phone(),
                loginUserId,
                limit,
                offset,
                holder.toMap()
            );

            updatePhoneList(responseData);

            loading.value = false;
        }

        async function loadPhoneCountryCode(
            loginUserId: string,
            holder: PhoneParameterHolder
        ) {
            loading.value = true;

            const responseData = await PsApiService.getPhoneList<Phone>(
                new Phone(),
                loginUserId,
                limit,
                offset,
                holder.toMap()
            );

            updatePhoneList(responseData);
            loading.value = false;
        }

        return {
            loading,
            limit,
            offset,
            isNoMoreRecord,
            id,
            phoneparamHolder,
            phoneList,
            loadPhoneCountryCode,
            filterPhoneUpdate,
        };
    })
);
