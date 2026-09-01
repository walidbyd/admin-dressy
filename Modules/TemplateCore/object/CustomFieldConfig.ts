import { PsObject } from "./core/PsObject";
export default class CustomFieldConfig extends PsObject<CustomFieldConfig>{

    timeFormat : string = '';

    init(
        timeFormat : string,
    ) {
        this.timeFormat = timeFormat;

        return this;
    }

    getPrimaryKey(): string {
        return 'custom';
    }

    toMap(object: CustomFieldConfig): any {
        const map = {};
        map['time_format'] = object.timeFormat;

        return map;
    }

    toMapList(objectList: CustomFieldConfig[]) : any[] {
        const mapList : any[] = [];
        for(let i = 0; i < objectList.length; i++) {
            if(objectList[i] != null) {
                mapList.push(this.toMap(objectList[i]));
            }
        }

        return mapList;
    }

    fromMap(obj: any) {
        return new CustomFieldConfig().init(
            obj.time_format,
       );
    }
    fromMapList(objList : any[] ) : CustomFieldConfig[] {
        const customFieldConfig : CustomFieldConfig[] = [];
        for(const obj in objList) {
            if(obj != null) {
                customFieldConfig.push(this.fromMap(obj));
            }
        }

        return customFieldConfig;
    }


}
