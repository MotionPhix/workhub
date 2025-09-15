<template>
  <Head title="Onboarding" />
  <div class="min-h-screen flex flex-col bg-background text-foreground">
    <header class="h-16 border-b bg-card/50 backdrop-blur-sm flex items-center">
      <div class="max-w-6xl mx-auto w-full px-4 flex items-center justify-between gap-6">
        <div class="flex items-center gap-2 font-semibold text-lg">
          <User class="h-5 w-5 text-primary" />
          <span>Welcome Aboard</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
          <span class="font-medium">Step {{ currentStep }} / {{ totalSteps }}</span>
          <div class="w-40 h-1.5 bg-muted rounded-full overflow-hidden">
            <div class="h-full bg-primary transition-all" :style="{ width: `${(currentStep/totalSteps)*100}%` }" />
          </div>
          <button v-if="currentStep < totalSteps" type="button" class="text-xs underline" @click="skipRemaining">Skip</button>
        </div>
      </div>
    </header>
    <main class="flex-1">
      <div class="max-w-4xl mx-auto px-4 py-10">
        <transition name="fade" mode="out-in">
          <section v-if="currentStep === 1" key="step1" class="space-y-8">
            <HeaderBlock icon="User" title="Personal Information" description="Tell us about you." />
            <form @submit.prevent="submitProfile" class="space-y-6">
              <div class="grid gap-6 md:grid-cols-2">
                <FormGroup label="First Name" for="first_name" :error="profileForm.errors.first_name">
                  <Input id="first_name" v-model="profileForm.first_name" autocomplete="given-name" required />
                </FormGroup>
                <FormGroup label="Last Name" for="last_name" :error="profileForm.errors.last_name">
                  <Input id="last_name" v-model="profileForm.last_name" autocomplete="family-name" required />
                </FormGroup>
                <FormGroup label="Email" for="email" :error="profileForm.errors.email">
                  <Input id="email" v-model="profileForm.email" type="email" :readonly="isInvitedUser" :class="{ 'opacity-75 pointer-events-none': isInvitedUser }" />
                </FormGroup>
                <FormGroup label="Timezone" for="timezone" :error="profileForm.errors.timezone">
                  <Select v-model="profileForm.timezone">
                    <SelectTrigger id="timezone" class="w-full">
                      <SelectValue placeholder="Select timezone" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="tz in timezones" :key="tz.value" :value="tz.value">{{ tz.label }}</SelectItem>
                    </SelectContent>
                  </Select>
                </FormGroup>
              </div>
              <div class="flex justify-end gap-3">
                <Button type="submit" :disabled="profileForm.processing">
                  <Loader2 v-if="profileForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                  Continue
                </Button>
              </div>
            </form>
          </section>
          <section v-else-if="currentStep === 2" key="step2" class="space-y-8">
            <HeaderBlock icon="Settings" title="Preferences" description="How do you want things to work?" />
            <form @submit.prevent="submitPreferences" class="space-y-8">
              <div class="grid gap-6 md:grid-cols-2">
                <PreferenceCard title="Email Notifications" description="Receive summary & important alerts">
                  <Switch v-model:checked="preferencesForm.email_notifications" />
                </PreferenceCard>
                <PreferenceCard title="Dark Mode" description="Enable dark theme">
                  <Switch v-model:checked="preferencesForm.dark_mode" />
                </PreferenceCard>
              </div>
              <div class="flex justify-between">
                <Button type="button" variant="outline" @click="previousStep">Back</Button>
                <Button type="submit" :disabled="preferencesForm.processing">
                  <Loader2 v-if="preferencesForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                  Continue
                </Button>
              </div>
            </form>
          </section>
          <section v-else-if="currentStep === 3" key="step3" class="space-y-8">
            <HeaderBlock icon="Users" title="Meet Your Team" description="A quick look at colleagues." />
            <div v-if="teamMembers.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="member in teamMembers" :key="member.id" class="p-4 border rounded-lg bg-card/50 flex flex-col items-center gap-2 text-center">
                <div class="w-16 h-16 rounded-full overflow-hidden bg-muted flex items-center justify-center">
                  <img v-if="member.avatar" :src="member.avatar" :alt="member.name" class="w-full h-full object-cover" />
                  <User v-else class="h-6 w-6 text-muted-foreground" />
                </div>
                <p class="text-sm font-medium">{{ member.name }}</p>
                <p class="text-xs text-muted-foreground">{{ member.job_title || 'Team Member' }}</p>
              </div>
            </div>
            <div v-else class="p-4 border rounded text-sm text-muted-foreground">No team members yet.</div>
            <div class="flex justify-between">
              <Button type="button" variant="outline" @click="previousStep">Back</Button>
              <Button type="button" @click="finishOnboarding">Finish</Button>
            </div>
          </section>
          <section v-else-if="currentStep === 4" key="step4" class="py-20 text-center space-y-6">
            <CheckCircle class="h-16 w-16 text-green-500 mx-auto" />
            <h2 class="text-3xl font-semibold">You're All Set!</h2>
            <p class="text-muted-foreground max-w-md mx-auto">Head to your dashboard to get started.</p>
            <Button type="button" @click="goToDashboard">Go to Dashboard</Button>
          </section>
        </transition>
      </div>
    </main>
  </div>
</template>
<script setup lang="ts">
import { ref, toRefs, h } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { User, Settings, Users, Loader2, CheckCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'

interface TimezoneOption { value: string; label: string }
interface TeamMember { id: number | string; name: string; job_title?: string | null; avatar?: string | null }
interface UserData { department?: { name: string; description?: string | null } | null; manager?: { name: string } | null }

const props = defineProps<{ user: UserData; timezones: TimezoneOption[]; teamMembers: TeamMember[]; isInvitedUser: boolean }>()
const { timezones, teamMembers, isInvitedUser } = toRefs(props)

const totalSteps = 4
const currentStep = ref(1)

const profileForm = useForm({ first_name: '', last_name: '', email: '', timezone: '' })
const preferencesForm = useForm({ email_notifications: true, dark_mode: false })

function goToStep(step: number) { if (step >= 1 && step <= totalSteps) { currentStep.value = step } }
function previousStep() { if (currentStep.value > 1) { currentStep.value-- } }
function skipRemaining() { goToStep(totalSteps) }

function submitProfile() { profileForm.post('/onboarding/profile', { onSuccess: () => goToStep(2) }) }
function submitPreferences() { preferencesForm.post('/onboarding/preferences', { onSuccess: () => goToStep(3) }) }
function finishOnboarding() { router.post('/onboarding/complete', {}, { onSuccess: () => goToStep(4) }) }
function goToDashboard() { router.visit('/dashboard') }

// Inline helpers
interface FormGroupProps { label: string; for: string; error?: string; hint?: string }
// @ts-ignore
const FormGroup = (p: FormGroupProps, { slots }: any) => h('div', { class: 'space-y-1' }, [ h('div', { class: 'flex items-center justify-between' }, [ h(Label, { for: p.for, class: 'block text-sm font-medium' }, p.label), p.hint ? h('span', { class: 'text-xs text-muted-foreground' }, p.hint) : null ]), slots.default?.(), p.error ? h('p', { class: 'text-xs text-destructive mt-1' }, p.error) : null ])

interface PreferenceCardProps { title: string; description: string }
// @ts-ignore
const PreferenceCard = (p: PreferenceCardProps, { slots }: any) => h('div', { class: 'border rounded-lg p-4 flex items-center justify-between gap-4 bg-card/50' }, [ h('div', { class: 'space-y-1' }, [ h('h3', { class: 'text-sm font-medium leading-none' }, p.title), h('p', { class: 'text-xs text-muted-foreground' }, p.description) ]), slots.default?.() ])

interface HeaderBlockProps { icon: string; title: string; description: string }
// @ts-ignore
const HeaderBlock = (p: HeaderBlockProps) => h('div', { class: 'space-y-2' }, [ h('h2', { class: 'text-xl font-semibold flex items-center gap-2' }, [ p.icon === 'User' ? h(User, { class: 'h-5 w-5 text-primary' }) : p.icon === 'Settings' ? h(Settings, { class: 'h-5 w-5 text-primary' }) : p.icon === 'Users' ? h(Users, { class: 'h-5 w-5 text-primary' }) : null, p.title ]), h('p', { class: 'text-sm text-muted-foreground max-w-prose' }, p.description) ])
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .25s, transform .25s; }
.fade-enter-from, .fade-leave-to { opacity:0; transform: translateY(4px); }
</style>
