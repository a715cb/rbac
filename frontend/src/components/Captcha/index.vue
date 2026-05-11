<template>
  <div class="captcha-container">
    <a-input
      id="login-captcha"
      v-model:value="captchaCode"
      name="captcha"
      placeholder="验证码"
      :maxlength="4"
      size="large"
      class="captcha-input"
      @keyup.enter="handleSubmit"
    >
      <template #prefix>
        <SafetyOutlined />
      </template>
    </a-input>
    <div class="captcha-image" :title="refreshTitle" @click="refreshCaptcha">
      <canvas ref="captchaCanvas" width="120" height="40" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { SafetyOutlined } from '@ant-design/icons-vue'

interface CaptchaEmits {
  (e: 'update:captcha', value: string): void
  (e: 'validate', value: boolean): void
}

const emit = defineEmits<CaptchaEmits>()

const captchaCode = ref('')
const captchaCanvas = ref<HTMLCanvasElement | null>(null)
const refreshTitle = '点击刷新验证码'

const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'
let captchaText = ''

const generateCaptcha = () => {
  captchaText = ''
  for (let i = 0; i < 4; i++) {
    captchaText += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  drawCaptcha()
}

const drawCaptcha = () => {
  const canvas = captchaCanvas.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  ctx.fillStyle = '#f0f0f0'
  ctx.fillRect(0, 0, canvas.width, canvas.height)

  for (let i = 0; i < 30; i++) {
    ctx.beginPath()
    ctx.strokeStyle = `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 0.3)`
    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.stroke()
  }

  ctx.font = 'bold 24px Arial'
  ctx.fillStyle = '#333'
  const textWidth = ctx.measureText(captchaText).width
  const startX = (canvas.width - textWidth) / 2
  for (let i = 0; i < captchaText.length; i++) {
    const x = startX + i * 25 + Math.random() * 10 - 5
    const y = 25 + Math.random() * 10 - 5
    const rotation = (Math.random() - 0.5) * 0.5
    ctx.save()
    ctx.translate(x, y)
    ctx.rotate(rotation)
    ctx.fillText(captchaText[i], 0, 0)
    ctx.restore()
  }

  for (let i = 0; i < 5; i++) {
    ctx.beginPath()
    ctx.strokeStyle = `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 0.5)`
    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.stroke()
  }
}

const refreshCaptcha = () => {
  generateCaptcha()
  captchaCode.value = ''
  emit('update:captcha', '')
  emit('validate', false)
}

const validateCaptcha = (): boolean => {
  const isValid = captchaCode.value.toLowerCase() === captchaText.toLowerCase()
  emit('validate', isValid)
  return isValid
}

const handleSubmit = () => {
  validateCaptcha()
}

watch(captchaCode, (val) => {
  emit('update:captcha', val)
  if (val.length === 4) {
    validateCaptcha()
  }
})

defineExpose({
  validate: validateCaptcha,
  refresh: refreshCaptcha,
  getCaptchaText: () => captchaText
})

onMounted(() => {
  generateCaptcha()
})
</script>

<style lang="less" scoped>
.captcha-container {
  display: flex;
  gap: 12px;
  align-items: center;
}

.captcha-input {
  flex: 1;
}

.captcha-image {
  cursor: pointer;
  border-radius: @border-radius-base;
  overflow: hidden;
  transition: opacity 0.3s;

  &:hover {
    opacity: 0.85;
  }
}
</style>
