<x-admin::layouts>
    <x-slot:title>
        @lang('bagisto::app.bagisto.credentials.index.title')
    </x-slot>

    <v-credentials />

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-credentials-template"
        >
            <div class="flex justify-between items-center">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('bagisto::app.bagisto.credentials.index.title')
                </p>
                <div class="flex gap-x-2.5 items-center">
                    <!-- Create Credential Button -->
                    @if (bouncer()->hasPermission('bagisto.credentials.store'))
                        <button
                            type="button"
                            class="primary-button"
                            @click="$refs.credentialCreateModal.toggle()"
                        >
                            @lang('bagisto::app.bagisto.credentials.index.create-btn')
                        </button>
                    @endif
                </div>
            </div>

            <x-admin::datagrid src="{{ route('admin.bagisto.credentials.index') }}" ref="datagrid" />

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit="handleSubmit($event, create)"
                    ref="createCredentialForm"
                >
                    <x-admin::modal ref="credentialCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('bagisto::app.bagisto.credentials.index.create.title')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <!-- Shop URl -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('bagisto::app.bagisto.credentials.index.create.shop_url')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="shop_url"
                                    name="shop_url"
                                    rules="required"
                                    :label="trans('bagisto::app.bagisto.credentials.index.create.shop_url')"
                                    :placeholder="trans('bagisto::app.bagisto.credentials.index.create.shop_url')"
                                />

                                <x-admin::form.control-group.error control-name="shop_url" />
                            </x-admin::form.control-group>

                            <!-- Email Address -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('bagisto::app.bagisto.credentials.index.create.email')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="email"
                                    name="email"
                                    rules="required"
                                    :label="trans('bagisto::app.bagisto.credentials.index.create.email')"
                                    :placeholder="trans('bagisto::app.bagisto.credentials.index.create.email')"
                                />

                                <x-admin::form.control-group.error control-name="email" />
                            </x-admin::form.control-group>

                            <!-- Password -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('bagisto::app.bagisto.credentials.index.create.password')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    id="password"
                                    name="password"
                                    rules="required"
                                    :label="trans('bagisto::app.bagisto.credentials.index.create.password')"
                                    :placeholder="trans('bagisto::app.bagisto.credentials.index.create.password')"
                                />

                                <x-admin::form.control-group.error control-name="password" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                    :disabled="isLoading"
                                >
                                    <!-- Spinner -->
                                    <svg v-if="isLoading" class="align-center inline-block animate-spin h-5 w-5 ml-2 text-white-700" xmlns="http://www.w3.org/2000/svg" fill="none"  aria-hidden="true" viewBox="0 0 24 24">
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        >
                                        </circle>

                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        >
                                        </path>
                                    </svg>

                                    @lang('bagisto::app.bagisto.credentials.index.create.save-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-credentials', {
                template: '#v-credentials-template',

                data() {
                    return {
                        isLoading: false,
                    }
                },

                methods: {
                    create(params, { resetForm, setErrors  }) {
                        this.isLoading = true;

                        let formData = new FormData(this.$refs.createCredentialForm);

                        formData.append('_method', 'post');

                        this.$axios.post("{{ route('admin.bagisto.credentials.store') }}", formData)
                        .then((response) => {
                            this.$refs.credentialCreateModal.close();

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                            this.$navigate(response.data.redirect_url);
                        })
                        .catch(error => {
                            if (error.response.status == 422) {
                                setErrors(error.response.data.errors);
                            }
                        }).then(() => {
                            this.isLoading = false;
                        });
                    },
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
