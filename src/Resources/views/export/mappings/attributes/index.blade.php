<x-admin::layouts.with-history>
    <x-slot:entityName>
        bagitsto_attribute_mapping
    </x-slot>

    <x-slot:title>
        @lang('bagisto::app.bagisto.export.mapping.attributes.title')
    </x-slot>

    <v-attribute-mapping 
        :bagisto-attributes='@json($bagistoAttributes)'
        :attributes='@json($attributes)'
    /> 

    @pushOnce('scripts')
        <script type="text/x-template" id="v-attribute-mapping-template">
            <x-admin::form
                id="bagisto-attribute-mapping-form"
                ajax
                :action="route('admin.bagisto.mappings.attributes.store')"
            >
                    <div class="flex justify-between items-center">
                        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                            @lang('bagisto::app.bagisto.export.mapping.attributes.title')
                        </p>

                    </div>

                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <div class="grid grid-cols-3 gap-10 items-center px-4 py-2.5 border-b bg-violet-50 dark:border-cherry-800 dark:bg-cherry-900 font-semibold">
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto::app.bagisto.export.mapping.attributes.bagisto-attribute')</p>
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto::app.bagisto.export.mapping.attributes.unopim-attribute')</p>
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto::app.bagisto.export.mapping.attributes.fixed-value')</p>
                                </div>

                                <div
                                    v-for="(bagistoAttribute, index) in standardBagistoAttributes"
                                    :key="index"
                                    class="grid grid-cols-3 gap-x-5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                                >
                                    <p 
                                        :title="bagistoAttribute.title" 
                                        class="break-words"
                                    >
                                        <span class="font-bold">@{{ bagistoAttribute.name }} [@{{ bagistoAttribute.code }}] </span> 
                                        <span 
                                            class="required text-red-600" 
                                            v-if="bagistoAttribute.required"
                                        >
                                        </span><br/>
                                        <small v-if="bagistoAttribute.title" class="text-gray-500"> <i class="icon-information text-xs"></i> @{{bagistoAttribute.title}}</small>
                                    </p>

                                    <!-- UnoPim Attribute -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.control
                                            type="select"
                                            ::id="'standard_attributes[' + bagistoAttribute.name + ']'"
                                            ::name="'standard_attributes[' + bagistoAttribute.name + ']'"
                                            @input="handleSelectChange($event, bagistoAttribute.code )"
                                            ::options="getAttributesByType(bagistoAttribute)"
                                            ::label="bagistoAttribute.name"
                                            ::placeholder="bagistoAttribute.name"
                                            ::value="selectMappedStandardAttribute(bagistoAttribute.code)"
                                            track-by="code"
                                            label-by="name"
                                        />

                                        <x-admin::form.control-group.error ::control-name="'standard_attributes[' + bagistoAttribute.name + ']'" />
                                    </x-admin::form.control-group>

                                    <!-- Fixed Value -->
                                    <x-admin::form.control-group class="!mb-0 flex gap-2.5">
                                        <x-admin::form.control-group.control
                                            type="text"
                                            ::id="'standard_attributes_default[' + bagistoAttribute.code + ']'"
                                            ::name="'standard_attributes_default[' + bagistoAttribute.code + ']'"
                                            ::value="selectMappedStandardAttributeDefault(bagistoAttribute.code) ?? bagistoAttribute.fixedValue"
                                            ::label="bagistoAttribute.name"
                                            ::disabled="isDisabled(bagistoAttribute.code)"
                                        />
                                        <!-- Remove Field Button (only for merged fields) -->
                                        <span v-if="!bagistoAttribute.id" class="mt-2">
                                            <button
                                                type="button"
                                                class=""
                                                @click="removeAttribute(index)"
                                            >
                                                <i class="icon-delete"></i>
                                            </button>
                                        </span>

                                        <x-admin::form.control-group.error ::control-name="'standard_attributes_default[' + bagistoAttribute.code + ']'" />
                                    </x-admin::form.control-group>

                                    
                                </div>
                            </div>

                            <v-additional-attribute-mapping
                                :attributes="attributes"
                                @add-attribute="addAdditionalAttribute"
                                @remove-attribute="removeAdditionalAttribute"
                            />
                        </div>
                    </div>

                    <input
                        type="hidden"
                        name="standard_attributes"
                        :value="JSON.stringify(standardAttributes)"
                    />
            </x-admin::form>
        </script>

        <script type="text/x-template" id="v-additional-attribute-mapping-template">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                    @lang('bagisto::app.bagisto.export.mapping.additional-attributes.title')
                </p>
                <span class="text-xs text-gray-500">@lang('bagisto::app.bagisto.export.mapping.additional-attributes.description')</span>
                
                <div>
                    <div class="grid grid-flow-row grid-cols-3 items-center justify-start gap-4">
                        <x-admin::form.control-group class="!mb-0 w-full">
                            <x-admin::form.control-group.control
                                type="text"
                                id="newBagistoAttributes"
                                name="newBagistoAttributes"
                                v-model="newBagistoAttributes"
                                label="Bagisto Attribute Code"
                                value=""
                                placeholder="Bagisto Attribute Code"
                                @input="handleInputChange"
                            />

                            <x-admin::form.control-group.error control-name="newBagistoAttributes" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="!mb-0 w-full">
                        @php
                            $supportedTypes = ['text', 'textarea', 'price', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'gallery', 'file', 'checkbox'];

                            $attributeTypes = [];

                            foreach($supportedTypes as $type) {
                                $attributeTypes[] = [
                                    'id'    => $type,
                                    'label' => trans('admin::app.catalog.attributes.create.'. $type)
                                ];
                            }

                            $attributeTypesJson = json_encode($attributeTypes);

                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            id="type"
                            class="cursor-pointer"
                            name="type"
                            :value="old('type')"
                            v-model="attributeType"
                            ref="attributeTypeGroup"
                            :label="trans('admin::app.catalog.attributes.create.type')"
                            placeholder="Select Attribute Type"
                            :options="$attributeTypesJson"
                            track-by="id"
                            label-by="label"
                        >
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="type" />
                    </x-admin::form.control-group>
                    <div>
                        <button
                            class="primary-button cursor-pointer"
                            @click="addBagistoAttribute"
                        >
                            @lang('bagisto::app.bagisto.export.mapping.attributes.add')
                        </button>
                    </div>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                    @lang('bagisto::app.bagisto.export.mapping.configurable-attributes.title')
                </p>
                <span class="text-xs text-gray-500">@lang('bagisto::app.bagisto.export.mapping.configurable-attributes.description')</span>

                <div class="grid grid-flow-row grid-cols-2 items-center justify-start gap-4 max-w-[60%]">
                    <!-- UnoPim Configurable Attribute -->
                    <x-admin::form.control-group class="!mb-0 w-full">
                        <x-admin::form.control-group.control
                            type="multiselect"
                            id="configurable_attribute"
                            name="configurable_attribute"
                            ref="configurableAttributes"
                            v-model="selectedAttributes"
                            :label="trans('bagisto::app.bagisto.export.mapping.configurable-attributes.title')"
                            :options="$configurableAttributes"
                            track-by="code"
                            label-by="name"
                        >
                            
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="configurable_attribute"/>
                    </x-admin::form.control-group>
                    <!-- Buttons for Select All / Deselect All -->
                    <div class="flex gap-2.5">
                        <button 
                            type="button" 
                            class="primary-button" 
                            @click="selectAll"
                            > 
                            @lang('bagisto::app.bagisto.export.mapping.attributes.select')
                        </button>

                        <button 
                            type="button" 
                            class="primary-button" 
                            @click="deselectAll"
                            > 
                            @lang('bagisto::app.bagisto.export.mapping.attributes.deselect')
                        </button>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-additional-attribute-mapping', {
                template: '#v-additional-attribute-mapping-template',
                props: ['attributes', 'configurableAttributes'],
                data() {
                    return {
                        timeout: null,
                        selectedType: '',
                        attributeType: '',
                        selectedAttributes: @json($configurableSelectedAttributes),
                        newBagistoAttributes: '',
                        allConfigurableOption: @json($configurableAttributes),
                        mappedAdditionalAttributes: @json($additionalAttributes),
                    };
                },
                mounted() {
                    if (this.selectedAttributes.length === 1 && this.selectedAttributes[0] === '') {
                        this.selectAll();
                    }
                },
                methods: {
                    selectAll() {
                        this.selectedAttributes = JSON.parse(this.allConfigurableOption);
                        this.$refs['configurableAttributes'].selectedValue = this.selectedAttributes;
                    },
                    deselectAll() {
                        this.$refs['configurableAttributes'].selectedValue = null;
                        this.selectedAttributes = [];
                    },
                    handleInputChange() {
                        clearTimeout(this.timeout);

                        this.timeout = setTimeout(() => {
                            this.fetchValue();
                        }, 1000);
                    },
                    fetchValue() {
                        this.$axios.get("{{ route('admin.bagisto.attributes.fetch') }}", {
                                params: { code: this.newBagistoAttributes },
                            })
                            .then(response => {
                                if (response.data.data) {
                                    this.attributeType = response.data.attribute.type;
                                    this.selectedType = { id: response.data.attribute.type, label: response.data.attribute.label };
                                    this.$refs.attributeTypeGroup.selectedValue = this.selectedType;
                                }
                            })
                            .catch(() => {});
                    },
                    addBagistoAttribute() {
                        if (!this.newBagistoAttributes || !this.attributeType) {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: @json(__('bagisto::app.bagisto.export.mapping.attributes.flash-message'))
                            });
                            return;
                        }
                        if (!window.translations) {
                            window.translations = {};
                        }
                        window.translations.title = "@lang('bagisto::app.bagisto.bagisto-attributes.title.title')";
                        const titleTemplate = window.translations.title;

                        const newAttribute = {
                            name: this.capitalizeFirstLetter(this.newBagistoAttributes),
                            code: this.newBagistoAttributes,
                            type: JSON.parse(this.attributeType).id,
                            title: titleTemplate
                                    .replace(':code', this.newBagistoAttributes)
                                    .replace(':type', JSON.parse(this.attributeType).id),
                        };

                        this.$emit('add-attribute', newAttribute);
                        this.mappedAdditionalAttributes.push(newAttribute);

                        this.$axios.post("{{ route('admin.bagisto.attributes.add') }}", newAttribute)
                            .then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });

                                if (this.$refs.attributeTypeGroup) {
                                    this.$refs.attributeTypeGroup.selectedValue = '';
                                }
                                this.newBagistoAttributes = '';
                                this.attributeType = '';
                            })
                            .catch(error => {
                                console.error(error);
                                this.mappedAdditionalAttributes.pop();
                            });
                    },
                    capitalizeFirstLetter(string) {
                        return string.charAt(0).toUpperCase() + string.slice(1);
                    },
                }
            });
        </script>

        <script type="module">
            app.component('v-attribute-mapping', {
                template: '#v-attribute-mapping-template',
                props: ['bagistoAttributes', 'attributes'],
                data() {
                    return {
                        standardAttributes: Object.assign({}, @json($standardAttributes?->mapped_value)),
                        standardAttributesDefaults: Object.assign({}, @json($standardAttributes?->fixed_value)),
                        additionalAttributes: [],
                        standardBagistoAttributes: this.bagistoAttributes,
                        mappedAdditionalAttributes: @json($additionalAttributes)
                    };
                },
                
                mounted() {
                    if (!window.translations) {
                        window.translations = {};
                    }
                    window.translations.title = "@lang('bagisto::app.bagisto.bagisto-attributes.title.title')";
                    const titleTemplate = window.translations.title;

                    const attributesWithTitle = this.mappedAdditionalAttributes.map(attribute => ({
                        ...attribute,
                        title: titleTemplate
                            .replace(':code', attribute.code)
                            .replace(':type', attribute.type),
                    }));

                    this.standardBagistoAttributes.push(...attributesWithTitle);
                },

                methods: {
                    handleSelectChange(value, fieldCode) {
                        try {
                            if (value) {
                                let selectedValue = JSON.parse(value);
                                this.standardAttributes[fieldCode] = selectedValue.code;
                            } else {
                                delete this.standardAttributes[fieldCode];
                            }
                        } catch (e) {}
                    },

                    isDisabled(code) {
                        return this.standardAttributes[code] ? true : false;
                    },

                    selectMappedStandardAttribute(fieldCode) {
                        return this.standardAttributes[fieldCode] ?? null;
                    },

                    selectMappedStandardAttributeDefault(fieldCode) {
                        return this.standardAttributesDefaults[fieldCode] ?? null;
                    },

                    getAttributesByType(bagistoAttribute) {
                        if (!bagistoAttribute || !bagistoAttribute.type) {
                            return [];
                        }

                        const types = bagistoAttribute.type.split(',').map(type => type.trim());

                        return this.attributes
                            .map(attribute => ({
                                ...attribute,
                                name: attribute.name && attribute.name.trim() ? attribute.name : attribute.code
                            }))
                            .filter(attribute => {
                                const matchesType = types.includes(attribute.type);
                                const matchesUnique = !bagistoAttribute.unique || attribute.is_unique;

                                return matchesType && matchesUnique;
                            });
                    },

                    addAdditionalAttribute(newAttribute) {
                        if (newAttribute) {
                            this.additionalAttributes.push(newAttribute);
                            this.standardBagistoAttributes.push(newAttribute);
                            if (this.selectedType) {
                                this.standardAttributes[newAttribute.code] = newAttribute.code;
                            }
                        }
                    },

                    removeAttribute(index) {
                        this.$emitter.emit('open-delete-modal', {
                            title: "@lang('bagisto::app.bagisto.export.mapping.modal-message.title')",
                            message: "@lang('bagisto::app.bagisto.export.mapping.modal-message.message')",
                            options: {
                                btnDisagree: "@lang('admin::app.components.modal.delete.disagree-btn')",
                                btnAgree: "@lang('bagisto::app.bagisto.export.mapping.attributes.remove')",
                                btnAgreeClass: 'danger-button',
                                btnDisagreeClass: 'transparent-button',
                            },
                            agree: () => {
                                let attributeCode = {
                                    code: this.standardBagistoAttributes[index].code
                                };
                                this.$axios.post("{{ route('admin.bagisto.attributes.remove') }}",
                                    attributeCode);
                                let removedItem = this.standardBagistoAttributes.splice(index, 1);
                            }
                        });
                    },
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
