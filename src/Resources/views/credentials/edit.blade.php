<x-admin::layouts.with-history>
    <x-slot:entityName>
        bagitsto_credentials
    </x-slot>

    <x-slot:title>
        @lang('bagisto::app.bagisto.credentials.edit.title')
    </x-slot>

    <x-admin::form
        ajax
        :action="route('admin.bagisto.credentials.update', ['id' => $credential->id])"
    >
        @method('PUT')
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('bagisto::app.bagisto.credentials.edit.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('admin.bagisto.credentials.index') }}"
                    class="transparent-button"
                >
                    @lang('bagisto::app.bagisto.credentials.edit.back-btn')
                </a>
            </div>
        </div>

        <!-- body content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Section -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                <!-- Currencies and Locale -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-gray-800 dark:text-white text-base  font-semibold">
                                @lang('bagisto::app.bagisto.credentials.edit.credential')
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <!-- Shop URl -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('bagisto::app.bagisto.credentials.edit.shop_url')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                class="cursor-not-allowed"
                                id="shop_url"
                                name="shop_url"
                                :value="$credential->shop_url"
                                rules="required"
                                readonly
                                :label="trans('bagisto::app.bagisto.credentials.edit.shop_url')"
                                :placeholder="trans('bagisto::app.bagisto.credentials.edit.shop_url')"
                            />

                            <x-admin::form.control-group.error control-name="shop_url" />
                        </x-admin::form.control-group>

                        <!-- Email Address -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('bagisto::app.bagisto.credentials.edit.email')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="email"
                                name="email"
                                :value="old('email') ?? $credential->email"
                                rules="required"
                                :label="trans('bagisto::app.bagisto.credentials.edit.email')"
                                :placeholder="trans('bagisto::app.bagisto.credentials.edit.email')"
                            />

                            <x-admin::form.control-group.error control-name="email" />
                        </x-admin::form.control-group>

                        <!-- Password -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('bagisto::app.bagisto.credentials.edit.password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                id="password"
                                name="password"
                                :value="old('password') ?? $credential->password"
                                rules="required"
                                :label="trans('bagisto::app.bagisto.credentials.edit.password')"
                                :placeholder="trans('bagisto::app.bagisto.credentials.edit.password')"
                            />

                            <x-admin::form.control-group.error control-name="password" />
                        </x-admin::form.control-group>
                        <small class="mt-1 text-red-600 text-xs italic">{{session()->has('credential') ? session('credential') : ''}}</small>
                    </x-slot>
                </x-admin::accordion>

                <!-- Currencies and Locale -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-gray-800 dark:text-white text-base  font-semibold">
                                @lang('bagisto::app.bagisto.credentials.edit.category-field-mapping')
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <!-- Email Address -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('bagisto::app.bagisto.credentials.edit.category-field-filterable')
                            </x-admin::form.control-group.label>
                            @php
                                $additionalInfo = $credential->additional_info ? $credential->additional_info[0]['filterableAttribtes'] : '';
                                $values = null;

                                if ($additionalInfo) {
                                    $attributeIds = explode(',', $additionalInfo);
                                    $values = json_encode($attributeIds);
                                }
                                $options = [];
                                foreach($storefilterableAttribtes as $attribute) {
                                    $options[] = [
                                        'id'    => (string)$attribute['id'],
                                        'label' => $attribute['name'] ?? $attribute['code'],
                                    ];
                                }
                        
                                $optionsInJson = json_encode($options);
                            @endphp

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.control
                                    type="multiselect"
                                    track-by="id"
                                    label-by="label"
                                    :options="$optionsInJson"
                                    :value="$values"
                                    id="filterableAttribtes"
                                    name="filterableAttribtes"
                                    rules="required"
                                />
                                <x-admin::form.control-group.error control-name="filterableAttribtes" />
                            </x-admin::form.control-group>

                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
            </div>

            <!-- Right Section -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('bagisto::app.bagisto.credentials.edit.store-config')
                    </p>
                    <v-store-config
                        :store-channels='@json($storeChannels)'
                        :channels='@json($unoPimChannels)'
                    />
                </div>
            </div>           
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-store-config-template">
            <div class="grid gap-2">
                <div
                    class="row grid grid-cols-2 grid-rows-1 gap-5 items-center border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                >
                    <div>
                        <p class="col-span-1 text-sm font-bold text-gray-800 dark:text-white">
                            @lang('bagisto::app.bagisto.credentials.edit.bagisto-channel')
                        </p>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-bold text-gray-800 dark:text-white">
                            @lang('bagisto::app.bagisto.credentials.edit.unopim-channel')
                        </p>
                    </div>
                </div>

                <input
                    type="hidden"
                    v-for="(mapping, index) in storeMappings"
                    :key="index"
                    :name="'store_info[' + index + ']'"
                    :value="JSON.stringify(mapping)"
                />

                <div
                    class="grid grid-cols-2 gap-2.5 px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                    v-for="(storeChannel, index) in storeChannels"
                >
                    <p class="text-sm text-gray-800 dark:text-white">
                        @{{ storeChannel['name'] }}
                    </p>
                    <div>
                        <div>
                            <!-- UnoPim Channel -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.control
                                    type="select"
                                    id="unoPimChannel"
                                    ::name="'channel['+ storeChannel['id']+ ']'"
                                    ::value="storeMappings[storeChannel['id']] ? storeMappings[storeChannel['id']]['channel'][storeChannel['code']] : ''"
                                    label="UnoPim Channel"
                                    ::options="channels"
                                    placeholder="UnoPim Channel"
                                    @input="handleChannelChange($event, storeChannel['id'])"
                                    track-by="code"
                                    label-by="name"
                                />

                                <x-admin::form.control-group.error ::control-name="'channel['+ storeChannel['id']+ ']'" />
                            </x-admin::form.control-group>
                        </div>
                        <div
                            class="row grid grid-cols-2 grid-rows-1 gap-5 items-center mb-3 border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            <div>
                                <p class="col-span-1 text-sm font-bold text-gray-800 dark:text-white">
                                    @lang('bagisto::app.bagisto.credentials.edit.bagisto-locale')
                                </p>
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-bold text-gray-800 dark:text-white">
                                    @lang('bagisto::app.bagisto.credentials.edit.unopim-locale')
                                </p>
                            </div>
                        </div>
                        <div
                            class="grid grid-cols-2 gap-2.5 px-4 items-center"
                            v-for="(storeLocale, index) in storeChannel['locales']"
                        >
                            <p class="text-sm text-gray-800 dark:text-white">
                                @{{ storeLocale['name'] }}
                            </p>

                            <div v-if="unoPimLocales[storeChannel['id']] && unoPimLocales[storeChannel['id']].length > 0">
                                <!-- UnoPim locale -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="unoPimLocale"
                                        ::name="'locale['+ storeLocale['code']+ ']'"
                                        label="UnoPim locale"
                                        ::options="unoPimLocales[storeChannel['id']]"
                                        ::value="storeMappings[storeChannel['id']]['locales'] ? storeMappings[storeChannel['id']]['locales'][storeLocale['code']] : ''"
                                        placeholder="UnoPim locale"
                                        @input="handleLocaleChange($event, storeChannel['id'], storeLocale['code'])"
                                        track-by="code"
                                        label-by="name"
                                    />

                                    <x-admin::form.control-group.error ::control-name="'locale['+ storeLocale['code']+ ']'" />
                                </x-admin::form.control-group>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-store-config', {
                template: '#v-store-config-template',
                props: ['storeChannels', 'channels'],
                data() {
                    return {
                        selectedStoreChannel: null,
                        unoPimChannel: {},
                        bagistoLocale: null,
                        unoPimLocales: [],
                        unoPimLocale: 'en_US',
                        storeMappings: @json($credential->store_info),
                        additionalInfo: @json($credential->additional_info),
                    };
                },

                mounted() {
                    this.bindData();
                },
                methods: {
                    handleChannelChange(value, channelId, locale = null) {
                        try {
                            if (value) {
                                let selectedValue = JSON.parse(value);
                                let storeChannel = this.storeChannels.find(c => c.id === channelId);
                                this.storeMappings[channelId] = {
                                    ...this.storeMappings[channelId],
                                    channel: { [storeChannel.code]: selectedValue.code },
                                };
                                this.unoPimLocales[channelId] = selectedValue.locales;
                            } else {
                                delete this.storeMappings[channelId];
                                this.unoPimLocales[channelId] = [];
                            }
                        } catch (e) {console.error(e)}
                    },

                    handleLocaleChange(value, channelId, locale) {
                        try {
                            if (value) {
                                let selectedValue = JSON.parse(value);
                                this.storeMappings[channelId]['locales'] = {...this.storeMappings[channelId]['locales'], [locale]: selectedValue.code};

                            } else {
                                delete this.storeMappings[channelId]['locales'][locale];
                            }
                        } catch (e) {console.error(e)}
                    },

                    bindData() {
                        Object.keys(this.storeMappings).forEach(key => {
                            let channelCode = this.storeMappings[key]?.channel ? Object.values(this.storeMappings[key].channel)[0] : null;
                            let channel = this.channels.find(c => c.code === channelCode);

                            this.unoPimLocales[key] = channel?.locales;
                        });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
