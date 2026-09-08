<x-admin::layouts.with-history>
    <x-slot:entityName>
        bagitsto_category_field_mapping
    </x-slot>

    <x-slot:title>
        @lang('bagisto::app.bagisto.export.mapping.category-fields.title')
    </x-slot>

    <v-category-field-mapping 
        :bagisto-category-fields='@json($bagistoCategoryFields)'
        :category-fields='@json($categoryFields)'
    /> 

    @pushOnce('scripts')
        <script type="text/x-template" id="v-category-field-mapping-template">
            <x-admin::form
                id="bagisto-category-field-mapping-form"
                ajax
                :action="route('admin.bagisto.mappings.category_fields.store')"
            >
                    <div class="flex justify-between items-center">
                        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                            @lang('bagisto::app.bagisto.export.mapping.category-fields.title')
                        </p>
                    </div>

                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <div class="grid grid-cols-3 gap-10 items-center px-4 py-2.5 border-b bg-violet-50 dark:border-cherry-800 dark:bg-cherry-900 font-semibold">
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto::app.bagisto.export.mapping.category-fields.bagisto-fields')</p>
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto::app.bagisto.export.mapping.category-fields.unopim-category-fields')</p>
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto::app.bagisto.export.mapping.category-fields.fixed-value')</p>
                                </div>

                                <div
                                    v-for="(bagostoField, index) in bagistoCategoryFields"
                                    :key="index"
                                    class="grid grid-cols-3 gap-10 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                                >
                                    <p 
                                        :title="bagostoField.title" 
                                        class="break-words items-center"
                                    >
                                        <span class="font-bold"> @{{ bagostoField.name }} [@{{ bagostoField.code }}] </span>
                                        <span 
                                            class="required text-red-600" 
                                            v-if="bagostoField.required"
                                        >
                                        </span><br/>
                                        <small class="text-gray-500"><i class="icon-information text-xs"></i> @{{bagostoField.title}}</small>
                                    </p>
                                    

                                    <!-- UnoPim CategoryField -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.control
                                            type="select"
                                            ::id="'standard_category_fields[' + bagostoField.name + ']'"
                                            ::name="'standard_category_fields[' + bagostoField.name + ']'"
                                            @input="handleSelectChange($event, bagostoField.code )"
                                            ::options="getFieldsByType(bagostoField)"
                                            ::label="bagostoField.name"
                                            ::placeholder="bagostoField.name"
                                            ::value="selectMappedStandardCategoryField(bagostoField.code)"
                                            track-by="code"
                                            label-by="name"
                                        />

                                        <x-admin::form.control-group.error ::control-name="'standard_category_fields[' + bagostoField.name + ']'" />
                                    </x-admin::form.control-group>

                                    <!-- Fixed Value -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.control
                                            type="text"
                                            ::id="'standard_category_fields_default[' + bagostoField.code + ']'"
                                            ::name="'standard_category_fields_default[' + bagostoField.code + ']'"
                                            ::value="selectMappedStandardCategoryFieldDefault(bagostoField.code) ?? bagostoField.fixedValue"
                                            ::label="bagostoField.name"
                                            ::disabled="isDisabled(bagostoField.code)"
                                        />

                                        <x-admin::form.control-group.error ::control-name="'standard_category_fields_default[' + bagostoField.code + ']'" />
                                    </x-admin::form.control-group>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input
                        type="hidden"
                        name="standard_category_fields"
                        :value="JSON.stringify(mappedCategoryFields)"
                    />
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-category-field-mapping', {
                template: '#v-category-field-mapping-template',
                props: ['bagistoCategoryFields', 'categoryFields'],
                data() {
                    return {
                        mappedCategoryFields:  Object.assign({}, @json($mappedCategoryFields?->mapped_value)),
                        mappedCategoryFieldsDefault:  Object.assign({}, @json($mappedCategoryFields?->fixed_value)),
                    };
                },

                methods: {
                    handleSelectChange(value, fieldCode) {
                        try {
                            if (value) {
                                let selectedValue = JSON.parse(value);
                                this.mappedCategoryFields[fieldCode] = selectedValue.code;  
                            } else {
                                delete this.mappedCategoryFields[fieldCode];
                            } 
                        } catch (e) {console.error(e)}
                    },

                    isDisabled(code) {
                        return this.mappedCategoryFields[code] ? true : false;
                    },

                    selectMappedStandardCategoryField(fieldCode) {
                        return this.mappedCategoryFields[fieldCode] ?? null;
                    },

                    selectMappedStandardCategoryFieldDefault(fieldCode) {
                        return this.mappedCategoryFieldsDefault[fieldCode] ?? null;
                    },

                    getFieldsByType(bagistoField) {
                        if (!bagistoField || !bagistoField.type) {
                            return [];
                        }

                        const types = bagistoField.type.split(',').map(type => type.trim());

                        return this.categoryFields
                            .map(field => ({
                                ...field,
                                name: field.name && field.name.trim() ? field.name : field.code
                            }))
                            .filter(field => {
                                const matchesType = types.includes(field.type);
                                const matchesUnique = !bagistoField.unique || field.is_unique;

                                return matchesType && matchesUnique;
                            });
                    },
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
