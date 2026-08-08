<template>
    <Form
        :id="formId"
        v-bind="formBinding"
        v-slot="{ errors, processing }"
        class="max-w-3xl space-y-6"
        @error="routeErrors"
    >
        <!-- Panels stay mounted: every field posts through a hidden input, and
             half the payload would go missing on whichever tab is not open. -->
        <Tabs v-model="tab" :unmount-on-hide="false">
            <TabsList>
                <TabsTrigger value="basic">
                    {{ $t('monitors.form.tabs.basic') }}
                    <!-- A rejection on the panel you cannot see otherwise reads
                         as a form that silently refuses to save. -->
                    <span v-if="basicErrorCount > 0" :class="errorPill">
                        {{ basicErrorCount }}
                    </span>
                </TabsTrigger>
                <TabsTrigger value="advanced">
                    {{ $t('monitors.form.tabs.advanced') }}
                    <span v-if="advancedErrorCount > 0" :class="errorPill">
                        {{ advancedErrorCount }}
                    </span>
                </TabsTrigger>
            </TabsList>

            <TabsContent value="basic" class="space-y-6 pt-4">
                <!-- 1. What to check -->
                <Section
                    :title="$t('monitors.form.sections.what')"
                    :description="$t('monitors.form.type.description')"
                >
                    <div class="space-y-6">
                        <!-- A card grid instead of a dropdown: each type explains
                             itself, so nobody has to guess what "keyword" means. -->
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="option in types"
                                :key="option"
                                type="button"
                                class="rounded-sm border p-3 text-left transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                :class="
                                    type === option
                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                        : ''
                                "
                                :aria-pressed="type === option"
                                @click="type = option"
                            >
                                <span
                                    class="flex items-center gap-2 text-sm font-medium"
                                >
                                    <MonitorTypeIcon
                                        :type="option"
                                        class="size-4 shrink-0"
                                    />
                                    {{
                                        $t(
                                            `monitors.form.type.options.${option}`,
                                        )
                                    }}
                                </span>
                                <span
                                    class="mt-1 block text-xs text-muted-foreground"
                                >
                                    {{
                                        $t(`monitors.form.type.hints.${option}`)
                                    }}
                                </span>
                            </button>
                        </div>
                        <input type="hidden" name="type" :value="type" />
                        <FieldError>{{ errors.type }}</FieldError>

                        <FieldGroup>
                            <Field>
                                <FieldLabel for="name">{{
                                    $t('monitors.form.name.title')
                                }}</FieldLabel>
                                <Input
                                    id="name"
                                    name="name"
                                    autocomplete="off"
                                    :placeholder="
                                        $t('monitors.form.name.placeholder')
                                    "
                                    :default-value="defaults?.name"
                                />
                                <FieldError>{{ errors.name }}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel for="url">
                                    {{
                                        expectsUrl
                                            ? $t('monitors.form.url.title')
                                            : $t('monitors.form.url.host_title')
                                    }}
                                </FieldLabel>
                                <Input
                                    id="url"
                                    name="url"
                                    autocomplete="off"
                                    :placeholder="
                                        expectsUrl
                                            ? $t(
                                                  'monitors.form.url.placeholder',
                                              )
                                            : $t(
                                                  'monitors.form.url.host_placeholder',
                                              )
                                    "
                                    :default-value="defaults?.url"
                                />
                                <FieldError>{{ errors.url }}</FieldError>
                                <FieldDescription>
                                    {{
                                        expectsUrl
                                            ? $t(
                                                  'monitors.form.url.description',
                                              )
                                            : $t(
                                                  'monitors.form.url.host_description',
                                              )
                                    }}
                                </FieldDescription>
                            </Field>

                            <!-- Keyword -->
                            <template v-if="type === 'keyword'">
                                <Field>
                                    <FieldLabel for="keyword">{{
                                        $t('monitors.form.config.keyword.title')
                                    }}</FieldLabel>
                                    <Input
                                        id="keyword"
                                        name="config[keyword]"
                                        :placeholder="
                                            $t(
                                                'monitors.form.config.keyword.placeholder',
                                            )
                                        "
                                        :default-value="
                                            defaults?.config?.keyword
                                        "
                                    />
                                    <FieldError>{{
                                        errors['config.keyword']
                                    }}</FieldError>
                                    <FieldDescription>{{
                                        $t(
                                            'monitors.form.config.keyword.description',
                                        )
                                    }}</FieldDescription>
                                </Field>
                                <Field orientation="horizontal">
                                    <FieldContent>
                                        <FieldLabel for="invert">{{
                                            $t(
                                                'monitors.form.config.invert.title',
                                            )
                                        }}</FieldLabel>
                                        <FieldDescription>{{
                                            $t(
                                                'monitors.form.config.invert.description',
                                            )
                                        }}</FieldDescription>
                                    </FieldContent>
                                    <Switch
                                        id="invert"
                                        v-model:checked="invert"
                                    />
                                </Field>
                            </template>

                            <!-- Port -->
                            <Field v-if="type === 'port'">
                                <FieldLabel for="port">{{
                                    $t('monitors.form.config.port.title')
                                }}</FieldLabel>
                                <Input
                                    id="port"
                                    v-model="port"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    class="sm:w-72"
                                />
                                <FieldError>{{
                                    errors['config.port']
                                }}</FieldError>
                                <FieldDescription>{{
                                    $t('monitors.form.config.port.description')
                                }}</FieldDescription>
                            </Field>

                            <!-- DNS -->
                            <template v-if="type === 'dns'">
                                <Field>
                                    <FieldLabel for="record_type">{{
                                        $t(
                                            'monitors.form.config.record_type.title',
                                        )
                                    }}</FieldLabel>
                                    <Select v-model="recordType">
                                        <SelectTrigger
                                            id="record_type"
                                            class="sm:w-72"
                                            ><SelectValue
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="record in recordTypes"
                                                :key="record"
                                                :value="record"
                                            >
                                                {{ record }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <FieldError>{{
                                        errors['config.record_type']
                                    }}</FieldError>
                                </Field>
                                <Field>
                                    <FieldLabel for="expected">{{
                                        $t(
                                            'monitors.form.config.expected.title',
                                        )
                                    }}</FieldLabel>
                                    <Input
                                        id="expected"
                                        v-model="expected"
                                        :placeholder="
                                            $t(
                                                'monitors.form.config.expected.placeholder',
                                            )
                                        "
                                    />
                                    <FieldError>{{
                                        errors['config.expected']
                                    }}</FieldError>
                                    <FieldDescription>{{
                                        $t(
                                            'monitors.form.config.expected.description',
                                        )
                                    }}</FieldDescription>
                                </Field>
                            </template>

                            <!-- SSL -->
                            <Field v-if="type === 'ssl'">
                                <FieldLabel for="warn_days">{{
                                    $t('monitors.form.config.warn_days.title')
                                }}</FieldLabel>
                                <Input
                                    id="warn_days"
                                    v-model="warnDays"
                                    type="number"
                                    min="1"
                                    max="365"
                                    class="sm:w-72"
                                />
                                <FieldError>{{
                                    errors['config.warn_days']
                                }}</FieldError>
                                <FieldDescription>{{
                                    $t(
                                        'monitors.form.config.warn_days.description',
                                    )
                                }}</FieldDescription>
                            </Field>
                        </FieldGroup>
                    </div>
                </Section>

                <!-- 2. Schedule -->
                <Section :title="$t('monitors.form.sections.schedule')">
                    <FieldGroup>
                        <Field>
                            <FieldLabel for="interval">{{
                                $t('monitors.form.check_interval.title')
                            }}</FieldLabel>
                            <Select v-model="intervalOption">
                                <SelectTrigger id="interval" class="sm:w-72"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="preset in intervalPresets"
                                        :key="preset.value"
                                        :value="preset.value"
                                    >
                                        {{
                                            $t(
                                                `monitors.form.check_interval.options.${preset.label}`,
                                            )
                                        }}
                                    </SelectItem>
                                    <SelectItem value="custom">{{
                                        $t('monitors.form.custom')
                                    }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                v-if="intervalOption === 'custom'"
                                v-model="intervalCustom"
                                type="number"
                                min="30"
                                class="sm:w-72"
                                :placeholder="
                                    $t(
                                        'monitors.form.check_interval.custom_placeholder',
                                    )
                                "
                            />
                            <FieldError>{{
                                errors.interval_seconds
                            }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.check_interval.description')
                            }}</FieldDescription>
                        </Field>

                        <Field orientation="horizontal">
                            <FieldContent>
                                <FieldLabel for="is_active">{{
                                    $t('monitors.form.is_active.title')
                                }}</FieldLabel>
                                <FieldDescription>{{
                                    $t('monitors.form.is_active.description')
                                }}</FieldDescription>
                            </FieldContent>
                            <Switch id="is_active" v-model:checked="isActive" />
                        </Field>
                    </FieldGroup>
                </Section>

                <!-- 3. Alerts -->
                <Section
                    :title="$t('monitors.form.sections.alerts')"
                    :description="$t('monitors.form.channels.description')"
                >
                    <p
                        v-if="channels.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ $t('monitors.form.channels.empty') }}
                        <Link
                            :href="integrationsRoute.index()"
                            class="underline"
                        >
                            {{ $t('monitors.form.channels.manage') }}
                        </Link>
                    </p>
                    <div v-else class="divide-y rounded-sm border">
                        <label
                            v-for="channel in channels"
                            :key="channel.uuid"
                            class="flex items-center gap-3 px-3 py-2.5 transition-colors"
                            :class="
                                coversEverything(channel)
                                    ? 'cursor-default'
                                    : 'cursor-pointer hover:bg-muted/40'
                            "
                        >
                            <!-- A channel scoped to every monitor already covers
                                 this one, so it is shown ticked and locked rather
                                 than implying a coverage gap that does not exist. -->
                            <Checkbox
                                :model-value="
                                    coversEverything(channel) ||
                                    selectedChannels.includes(channel.uuid)
                                "
                                :disabled="coversEverything(channel)"
                                @update:model-value="
                                    toggleChannel(channel.uuid)
                                "
                            />
                            <span class="min-w-0">
                                <span class="block text-sm font-medium">{{
                                    channel.name
                                }}</span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        $t(`integrations.types.${channel.type}`)
                                    }}
                                    · {{ channel.destination }}
                                    <template v-if="coversEverything(channel)">
                                        ·
                                        {{
                                            $t(
                                                'monitors.form.channels.covers_all',
                                            )
                                        }}
                                    </template>
                                </span>
                            </span>
                        </label>
                    </div>
                </Section>
            </TabsContent>

            <TabsContent value="advanced" class="space-y-6 pt-4">
                <!-- Request options, shared by the plain and keyword checks -->
                <Section
                    v-if="type === 'http' || type === 'keyword'"
                    :title="$t('monitors.form.sections.request')"
                    :description="$t('monitors.form.sections.request_hint')"
                >
                    <FieldGroup>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <Field>
                                <FieldLabel for="method">{{
                                    $t('monitors.form.config.method.title')
                                }}</FieldLabel>
                                <Select v-model="method">
                                    <SelectTrigger id="method"
                                        ><SelectValue
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="verb in methods"
                                            :key="verb"
                                            :value="verb"
                                            >{{ verb }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <FieldError>{{
                                    errors['config.method']
                                }}</FieldError>
                            </Field>
                            <Field>
                                <FieldLabel for="expected_status_codes">{{
                                    $t(
                                        'monitors.form.config.expected_status_codes.title',
                                    )
                                }}</FieldLabel>
                                <Input
                                    id="expected_status_codes"
                                    v-model="expectedStatusCodes"
                                    :placeholder="
                                        $t(
                                            'monitors.form.config.expected_status_codes.placeholder',
                                        )
                                    "
                                />
                                <FieldError>{{
                                    errors['config.expected_status_codes'] ??
                                    errors['config.expected_status_codes.0']
                                }}</FieldError>
                                <FieldDescription>{{
                                    $t(
                                        'monitors.form.config.expected_status_codes.description',
                                    )
                                }}</FieldDescription>
                            </Field>
                        </div>

                        <Field orientation="horizontal">
                            <FieldContent>
                                <FieldLabel for="verify_ssl">{{
                                    $t('monitors.form.config.verify_ssl.title')
                                }}</FieldLabel>
                                <FieldDescription>{{
                                    $t(
                                        'monitors.form.config.verify_ssl.description',
                                    )
                                }}</FieldDescription>
                            </FieldContent>
                            <Switch
                                id="verify_ssl"
                                v-model:checked="verifySsl"
                            />
                        </Field>

                        <Field orientation="horizontal">
                            <FieldContent>
                                <FieldLabel for="follow_redirects">{{
                                    $t(
                                        'monitors.form.config.follow_redirects.title',
                                    )
                                }}</FieldLabel>
                                <FieldDescription>{{
                                    $t(
                                        'monitors.form.config.follow_redirects.description',
                                    )
                                }}</FieldDescription>
                            </FieldContent>
                            <Switch
                                id="follow_redirects"
                                v-model:checked="followRedirects"
                            />
                        </Field>

                        <Field>
                            <FieldLabel for="auth_type">{{
                                $t('monitors.form.config.auth.title')
                            }}</FieldLabel>
                            <Select v-model="authType">
                                <SelectTrigger id="auth_type" class="sm:w-72"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in authTypes"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{
                                            $t(
                                                `monitors.form.config.auth.options.${option}`,
                                            )
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldDescription>{{
                                $t('monitors.form.config.auth.description')
                            }}</FieldDescription>
                        </Field>

                        <div
                            v-if="authType === 'basic'"
                            class="grid gap-4 sm:grid-cols-2"
                        >
                            <Field>
                                <FieldLabel for="auth_username">{{
                                    $t('monitors.form.config.auth.username')
                                }}</FieldLabel>
                                <Input
                                    id="auth_username"
                                    v-model="authUsername"
                                    autocomplete="off"
                                />
                                <FieldError>{{
                                    errors['config.auth_username']
                                }}</FieldError>
                            </Field>
                            <Field>
                                <FieldLabel for="auth_password">{{
                                    $t('monitors.form.config.auth.password')
                                }}</FieldLabel>
                                <Input
                                    id="auth_password"
                                    v-model="authPassword"
                                    type="password"
                                    autocomplete="off"
                                />
                                <FieldError>{{
                                    errors['config.auth_password']
                                }}</FieldError>
                            </Field>
                        </div>

                        <Field v-if="authType === 'bearer'">
                            <FieldLabel for="auth_token">{{
                                $t('monitors.form.config.auth.token')
                            }}</FieldLabel>
                            <Input
                                id="auth_token"
                                v-model="authToken"
                                type="password"
                                autocomplete="off"
                            />
                            <FieldError>{{
                                errors['config.auth_token']
                            }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.config.secret_masked')
                            }}</FieldDescription>
                        </Field>

                        <Field>
                            <FieldLabel for="headers">{{
                                $t('monitors.form.config.headers.title')
                            }}</FieldLabel>
                            <textarea
                                id="headers"
                                v-model="headersText"
                                rows="3"
                                spellcheck="false"
                                class="w-full rounded-sm border bg-transparent px-3 py-2 font-mono text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :placeholder="
                                    $t(
                                        'monitors.form.config.headers.placeholder',
                                    )
                                "
                            ></textarea>
                            <FieldError>{{
                                errors['config.headers']
                            }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.config.headers.description')
                            }}</FieldDescription>
                        </Field>

                        <Field>
                            <FieldLabel for="body">{{
                                $t('monitors.form.config.body.title')
                            }}</FieldLabel>
                            <textarea
                                id="body"
                                v-model="body"
                                rows="3"
                                spellcheck="false"
                                class="w-full rounded-sm border bg-transparent px-3 py-2 font-mono text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            ></textarea>
                            <FieldError>{{ errors['config.body'] }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.config.body.description')
                            }}</FieldDescription>
                        </Field>

                        <Field v-if="body.trim() !== ''">
                            <FieldLabel for="content_type">{{
                                $t('monitors.form.config.content_type.title')
                            }}</FieldLabel>
                            <Select v-model="contentType">
                                <SelectTrigger id="content_type" class="sm:w-72"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in contentTypes"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ option }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </Field>
                    </FieldGroup>
                </Section>

                <Section
                    :title="$t('monitors.form.sections.reliability')"
                    :description="$t('monitors.form.sections.reliability_hint')"
                >
                    <FieldGroup>
                        <Field>
                            <FieldLabel for="timeout">{{
                                $t('monitors.form.timeout.title')
                            }}</FieldLabel>
                            <Select v-model="timeoutOption">
                                <SelectTrigger id="timeout" class="sm:w-72"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="preset in timeoutPresets"
                                        :key="preset.value"
                                        :value="preset.value"
                                    >
                                        {{
                                            $t(
                                                `monitors.form.timeout.options.${preset.label}`,
                                            )
                                        }}
                                    </SelectItem>
                                    <SelectItem value="custom">{{
                                        $t('monitors.form.custom')
                                    }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                v-if="timeoutOption === 'custom'"
                                v-model="timeoutCustom"
                                type="number"
                                min="1"
                                class="sm:w-72"
                                :placeholder="
                                    $t(
                                        'monitors.form.timeout.custom_placeholder',
                                    )
                                "
                            />
                            <FieldError>{{ errors.timeout }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.timeout.description')
                            }}</FieldDescription>
                        </Field>

                        <Field>
                            <FieldLabel for="confirmation">{{
                                $t('monitors.form.confirmation_threshold.title')
                            }}</FieldLabel>
                            <Select v-model="confirmation">
                                <SelectTrigger id="confirmation" class="sm:w-72"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="value in confirmationOptions"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{
                                            $t(
                                                `monitors.form.confirmation_threshold.options.${value}`,
                                            )
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError>{{
                                errors.confirmation_threshold
                            }}</FieldError>
                            <FieldDescription>{{
                                $t(
                                    'monitors.form.confirmation_threshold.description',
                                )
                            }}</FieldDescription>
                        </Field>

                        <Field>
                            <FieldLabel for="recovery">{{
                                $t('monitors.form.recovery_threshold.title')
                            }}</FieldLabel>
                            <Select v-model="recovery">
                                <SelectTrigger id="recovery" class="sm:w-72"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="value in recoveryOptions"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{
                                            $t(
                                                `monitors.form.recovery_threshold.options.${value}`,
                                            )
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError>{{
                                errors.recovery_threshold
                            }}</FieldError>
                            <FieldDescription>{{
                                $t(
                                    'monitors.form.recovery_threshold.description',
                                )
                            }}</FieldDescription>
                        </Field>

                        <Field>
                            <FieldLabel for="degraded_response_ms">{{
                                $t('monitors.form.degraded_response_ms.title')
                            }}</FieldLabel>
                            <Input
                                id="degraded_response_ms"
                                v-model="degradedMs"
                                type="number"
                                min="1"
                                class="sm:w-72"
                                :placeholder="
                                    $t(
                                        'monitors.form.degraded_response_ms.placeholder',
                                    )
                                "
                            />
                            <FieldError>{{
                                errors.degraded_response_ms
                            }}</FieldError>
                            <FieldDescription>{{
                                $t(
                                    'monitors.form.degraded_response_ms.description',
                                )
                            }}</FieldDescription>
                        </Field>
                    </FieldGroup>
                </Section>
            </TabsContent>
        </Tabs>

        <!-- Every value posts from here rather than from the panel that edits
             it, so a field cannot go missing with the tab it lives on. -->
        <div hidden>
            <input
                type="hidden"
                name="interval_seconds"
                :value="intervalValue"
            />
            <input type="hidden" name="timeout" :value="timeoutValue" />
            <input
                type="hidden"
                name="confirmation_threshold"
                :value="confirmation"
            />
            <input type="hidden" name="recovery_threshold" :value="recovery" />
            <input
                type="hidden"
                name="degraded_response_ms"
                :value="degradedMs"
            />
            <input
                type="hidden"
                name="is_active"
                :value="isActive ? '1' : '0'"
            />

            <input
                v-for="(value, key) in configFields"
                :key="key"
                type="hidden"
                :name="`config[${key}]`"
                :value="value"
            />

            <!-- Indexed and keyed collections keep their own loops: their
                 names carry a position or a header name, so they cannot be
                 expressed as flat pairs. -->
            <template v-if="type === 'http' || type === 'keyword'">
                <input
                    v-for="(code, index) in statusCodeList"
                    :key="code"
                    type="hidden"
                    :name="`config[expected_status_codes][${index}]`"
                    :value="code"
                />
                <input
                    v-for="entry in headerEntries"
                    :key="entry.name"
                    type="hidden"
                    :name="`config[headers][${entry.name}]`"
                    :value="entry.value"
                />
            </template>

            <input
                v-for="uuid in selectedChannels"
                :key="`sel-${uuid}`"
                type="hidden"
                name="notification_channels[]"
                :value="uuid"
            />
            <!-- Keeps the key present when everything is unticked, so an
                 update can clear the list rather than silently keep it. -->
            <input
                v-if="selectedChannels.length === 0"
                type="hidden"
                name="notification_channels[]"
                value=""
            />
        </div>

        <!-- Proving the check works before saving it is the whole point: the
             alternative is saving and waiting out an interval to discover a
             typo in the URL. -->
        <div
            v-if="testResult"
            class="flex items-start gap-2.5 rounded-sm border p-3 text-sm"
            :class="
                testResult.is_up
                    ? 'border-emerald-600/30 bg-emerald-500/5'
                    : 'border-destructive/30 bg-destructive/5'
            "
            role="status"
        >
            <component
                :is="testResult.is_up ? CheckCircle2Icon : XCircleIcon"
                class="mt-0.5 size-4 shrink-0"
                :class="
                    testResult.is_up
                        ? 'text-emerald-700 dark:text-emerald-400'
                        : 'text-destructive'
                "
                aria-hidden="true"
            />
            <div class="min-w-0">
                <p class="font-medium">
                    {{
                        testResult.is_up
                            ? $t('monitors.preview.up', {
                                  duration: formatResponseMs(
                                      testResult.response_ms,
                                  ),
                              })
                            : $t('monitors.preview.down')
                    }}
                    <span
                        v-if="testResult.status_code"
                        class="font-normal text-muted-foreground"
                    >
                        ·
                        {{
                            $t('monitors.preview.status', {
                                code: testResult.status_code,
                            })
                        }}
                    </span>
                </p>
                <p v-if="testResult.error" class="mt-0.5 text-muted-foreground">
                    {{ testResult.error }}
                </p>
            </div>
        </div>

        <div
            class="flex flex-wrap items-center justify-between gap-2 border-t pt-4"
        >
            <div class="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="testing"
                    @click="testCheck"
                >
                    <Spinner v-if="testing" />
                    <FlaskConicalIcon v-else />
                    {{ $t('monitors.preview.action') }}
                </Button>
                <p class="hidden text-xs text-muted-foreground sm:block">
                    {{ $t('monitors.preview.hint') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    :as="Link"
                    variant="ghost"
                    :href="monitorsRoute.index()"
                >
                    {{ $t('base.cancel') }}
                </Button>
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    {{ $t('monitors.form.submit') }}
                </Button>
            </div>
        </div>
    </Form>
</template>

<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import {
    CheckCircle2Icon,
    FlaskConicalIcon,
    XCircleIcon,
} from 'lucide-vue-next';
import { computed, ref, useId, watch } from 'vue';
import MonitorTypeIcon from '@/components/monitors/MonitorTypeIcon.vue';
import Section from '@/components/Section.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatResponseMs } from '@/lib/format';
import { csrfHeaders } from '@/lib/http';
import { trans } from '@/lib/i18n';
import * as integrationsRoute from '@/routes/integrations';
import * as monitorsRoute from '@/routes/monitors';
import type {
    Monitor,
    MonitorType,
    MonitorTypeOptions,
    NotificationChannel,
} from '@/types/monitors';

const props = withDefaults(
    defineProps<{
        types: MonitorType[];
        typeOptions: MonitorTypeOptions;
        channels: NotificationChannel[];
        form?:
            | ReturnType<typeof monitorsRoute.store.form>
            | ReturnType<typeof monitorsRoute.update.form>;
        defaults?: Partial<Monitor>;
    }>(),
    {},
);

const formBinding = computed(() => props.form ?? monitorsRoute.store.form());

const errorPill =
    'rounded-full bg-destructive px-1.5 text-xs leading-5 font-medium text-white';

// Server-supplied, so these can never offer a value the profiles reject.
const methods = computed(() => props.typeOptions.methods);
const authTypes = computed(() => props.typeOptions.auth_types);
const contentTypes = computed(() => props.typeOptions.content_types);
const recordTypes = computed(() => props.typeOptions.record_types);

const confirmationOptions = [1, 2, 3, 5];
const recoveryOptions = [1, 2, 3, 5];

const intervalPresets = [
    { value: 30, label: '30s' },
    { value: 60, label: '1m' },
    { value: 300, label: '5m' },
    { value: 600, label: '10m' },
    { value: 1800, label: '30m' },
    { value: 3600, label: '1h' },
];

const timeoutPresets = [
    { value: 5, label: '5s' },
    { value: 10, label: '10s' },
    { value: 30, label: '30s' },
    { value: 60, label: '60s' },
];

/** Everything the advanced panel owns; anything else belongs to basic. */
const advancedFields = [
    'timeout',
    'confirmation_threshold',
    'recovery_threshold',
    'degraded_response_ms',
    'config.method',
    'config.expected_status_codes',
    'config.verify_ssl',
    'config.follow_redirects',
    'config.auth_type',
    'config.auth_username',
    'config.auth_password',
    'config.auth_token',
    'config.headers',
    'config.body',
    'config.content_type',
];

const tab = ref<'basic' | 'advanced'>('basic');
const rejected = ref<Record<string, string>>({});

function isAdvanced(key: string) {
    return advancedFields.some(
        (field) => key === field || key.startsWith(`${field}.`),
    );
}

const advancedErrorCount = computed(
    () => Object.keys(rejected.value).filter(isAdvanced).length,
);
const basicErrorCount = computed(
    () => Object.keys(rejected.value).length - advancedErrorCount.value,
);

function routeErrors(errors: Record<string, string>) {
    rejected.value = errors;

    if (basicErrorCount.value === 0 && advancedErrorCount.value > 0) {
        tab.value = 'advanced';
    } else if (basicErrorCount.value > 0) {
        tab.value = 'basic';
    }
}

type PreviewResult = {
    is_up: boolean;
    response_ms: number;
    error: string | null;
    status_code?: number | null;
};

// The <form> is the payload: reading it back gives exactly what a save would
// send, hidden inputs and all, with no second serialiser to keep in step.
const formId = `monitor-form-${useId()}`;
const testing = ref(false);
const testResult = ref<PreviewResult | null>(null);

async function testCheck() {
    const form = document.getElementById(formId);

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const body = new FormData(form);

    // Lets the server swap masked credentials back in, so testing an existing
    // monitor without retyping its password does not send it asterisks.
    if (props.defaults?.uuid) {
        body.set('monitor', props.defaults.uuid);
    }

    testing.value = true;
    testResult.value = null;

    try {
        const response = await fetch(monitorsRoute.preview().url, {
            method: 'POST',
            headers: { Accept: 'application/json', ...csrfHeaders() },
            body,
        });

        if (response.status === 422) {
            const payload = (await response.json()) as {
                errors?: Record<string, string[]>;
            };
            const errors = Object.fromEntries(
                Object.entries(payload.errors ?? {}).map(([key, messages]) => [
                    key,
                    messages[0],
                ]),
            );

            routeErrors(errors);
            testResult.value = {
                is_up: false,
                response_ms: 0,
                error:
                    Object.values(errors)[0] ??
                    trans('monitors.preview.failed'),
            };

            return;
        }

        testResult.value = response.ok
            ? ((await response.json()) as PreviewResult)
            : {
                  is_up: false,
                  response_ms: 0,
                  error: trans('monitors.preview.failed'),
              };
    } catch {
        testResult.value = {
            is_up: false,
            response_ms: 0,
            error: trans('monitors.preview.failed'),
        };
    } finally {
        testing.value = false;
    }
}

const type = ref<MonitorType>(props.defaults?.type ?? props.types[0]);
const expectsUrl = computed(() =>
    props.typeOptions.url_types.includes(type.value),
);

const invert = ref(props.defaults?.config?.invert ?? false);
const verifySsl = ref(props.defaults?.config?.verify_ssl ?? true);
const method = ref(props.defaults?.config?.method ?? 'GET');
/**
 * Free text rather than a tag input: the accepted forms ("204", "200-299",
 * "2xx") are short enough to type and the server validates each one, so a
 * chip editor would be ceremony around a comma.
 */
const expectedStatusCodes = ref<string>(
    (props.defaults?.config?.expected_status_codes ?? []).join(', ') ||
        (props.defaults?.config?.expected_status
            ? String(props.defaults.config.expected_status)
            : ''),
);

const statusCodeList = computed(() =>
    expectedStatusCodes.value
        .split(',')
        .map((code) => code.trim())
        .filter((code) => code !== ''),
);

const followRedirects = ref(props.defaults?.config?.follow_redirects ?? true);

const authType = ref<string>(props.defaults?.config?.auth_type ?? 'none');
const authUsername = ref<string>(props.defaults?.config?.auth_username ?? '');
const authPassword = ref<string>(props.defaults?.config?.auth_password ?? '');
const authToken = ref<string>(props.defaults?.config?.auth_token ?? '');
const body = ref<string>(props.defaults?.config?.body ?? '');
const contentType = ref<string>(
    props.defaults?.config?.content_type ?? 'application/json',
);

// One "Name: value" per line — the way headers are written everywhere else.
const headersText = ref<string>(
    Object.entries(props.defaults?.config?.headers ?? {})
        .map(([name, value]) => `${name}: ${value}`)
        .join('\n'),
);

const headerEntries = computed(() =>
    headersText.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line !== '' && line.includes(':'))
        .map((line) => {
            const index = line.indexOf(':');

            return {
                name: line.slice(0, index).trim(),
                value: line.slice(index + 1).trim(),
            };
        })
        .filter((entry) => entry.name !== ''),
);
const port = ref<number | string>(props.defaults?.config?.port ?? 443);
const recordType = ref(props.defaults?.config?.record_type ?? 'A');
const expected = ref<string>(props.defaults?.config?.expected ?? '');
const warnDays = ref<number | string>(props.defaults?.config?.warn_days ?? 14);

/**
 * The type-specific config this form posts, as flat `key => value` pairs.
 *
 * Everything here is edited by a control that is not itself a form input — a
 * switch, a select, a chip list — so each value needs a hidden input to reach
 * the server. Declaring which keys a type contributes once, here, keeps that
 * list from being restated as a second set of `v-if`s in the markup, and
 * keeps it beside the panel logic it has to agree with.
 *
 * The save and the "Test check" button both read the rendered form with
 * `new FormData()`, so this is the payload for both.
 */
const configFields = computed<Record<string, string>>(() => {
    const fields: Record<string, string> = {};

    if (type.value === 'keyword') {
        fields.invert = invert.value ? '1' : '0';
    }

    if (type.value === 'port') {
        fields.port = String(port.value);
    }

    if (type.value === 'dns') {
        fields.record_type = recordType.value;
        fields.expected = expected.value;
    }

    if (type.value === 'ssl') {
        fields.warn_days = String(warnDays.value);
    }

    if (type.value === 'http' || type.value === 'keyword') {
        fields.method = method.value;
        fields.verify_ssl = verifySsl.value ? '1' : '0';
        fields.follow_redirects = followRedirects.value ? '1' : '0';
        fields.auth_type = authType.value;
        fields.body = body.value;

        if (authType.value === 'basic') {
            fields.auth_username = authUsername.value;
            fields.auth_password = authPassword.value;
        }

        if (authType.value === 'bearer') {
            fields.auth_token = authToken.value;
        }

        // Only meaningful with a body, and posting it otherwise would set a
        // content type on a request that carries nothing.
        if (body.value.trim() !== '') {
            fields.content_type = contentType.value;
        }
    }

    return fields;
});

const initialInterval = props.defaults?.interval_seconds ?? 300;
const intervalOption = ref<number | 'custom'>(
    intervalPresets.some((preset) => preset.value === initialInterval)
        ? initialInterval
        : 'custom',
);
const intervalCustom = ref<number | string>(
    intervalPresets.some((preset) => preset.value === initialInterval)
        ? ''
        : initialInterval,
);
const intervalValue = computed(() =>
    intervalOption.value === 'custom'
        ? intervalCustom.value
        : intervalOption.value,
);

const initialTimeout = props.defaults?.timeout ?? 10;
const timeoutOption = ref<number | 'custom'>(
    timeoutPresets.some((preset) => preset.value === initialTimeout)
        ? initialTimeout
        : 'custom',
);
const timeoutCustom = ref<number | string>(
    timeoutPresets.some((preset) => preset.value === initialTimeout)
        ? ''
        : initialTimeout,
);
const timeoutValue = computed(() =>
    timeoutOption.value === 'custom'
        ? timeoutCustom.value
        : timeoutOption.value,
);

const confirmation = ref(props.defaults?.confirmation_threshold ?? 1);
const recovery = ref(props.defaults?.recovery_threshold ?? 1);
const degradedMs = ref<number | string>(
    props.defaults?.degraded_response_ms ?? '',
);
const isActive = ref(props.defaults?.is_active ?? true);

const selectedChannels = ref<string[]>(
    (props.defaults?.notification_channels ?? []).map(
        (channel) => channel.uuid,
    ),
);

// Alerts on every monitor its owner has, so this form cannot detach it — that
// is a decision made on the integration itself.
function coversEverything(channel: NotificationChannel) {
    return channel.alert_scope === 'all';
}

function toggleChannel(uuid: string) {
    const channel = props.channels.find((item) => item.uuid === uuid);

    if (channel && coversEverything(channel)) {
        return;
    }

    selectedChannels.value = selectedChannels.value.includes(uuid)
        ? selectedChannels.value.filter((value) => value !== uuid)
        : [...selectedChannels.value, uuid];
}

// A port check on "example.com:6379" should not silently keep the 443 default.
watch(type, (next) => {
    if (next === 'port' && !props.defaults?.config?.port) {
        port.value = 443;
    }
});
</script>
