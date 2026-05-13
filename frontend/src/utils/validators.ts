import type { Rule } from 'ant-design-vue/es/form'

const PASSWORD_MIN_LENGTH = 8
const PASSWORD_MAX_LENGTH = 20
const PASSWORD_SPECIAL_CHARS = '@$!%*?&'
const PASSWORD_PATTERN = new RegExp(
  `^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[${PASSWORD_SPECIAL_CHARS}])[A-Za-z\\d${PASSWORD_SPECIAL_CHARS}]{${PASSWORD_MIN_LENGTH},}$`
)

function getPasswordStrengthMessage(): string {
  return `密码长度为 ${PASSWORD_MIN_LENGTH}-${PASSWORD_MAX_LENGTH} 位，需包含大小写字母、数字和特殊字符（${PASSWORD_SPECIAL_CHARS}）`
}

function validatePasswordStrength(_rule: unknown, value: string): Promise<void> {
  if (!value) return Promise.resolve()
  if (!PASSWORD_PATTERN.test(value)) {
    return Promise.reject(new Error(getPasswordStrengthMessage()))
  }
  return Promise.resolve()
}

export function getPasswordRules(required = true): Rule[] {
  const rules: Rule[] = []
  if (required) {
    rules.push({ required: true, message: '请输入密码', trigger: 'blur' })
  }
  rules.push(
    {
      min: PASSWORD_MIN_LENGTH,
      max: PASSWORD_MAX_LENGTH,
      message: getPasswordStrengthMessage(),
      trigger: 'blur'
    },
    { validator: validatePasswordStrength, trigger: 'blur' }
  )
  return rules
}

export function getConfirmPasswordRules(
  getPassword: () => string | undefined,
  required = true
): Rule[] {
  const rules: Rule[] = []
  if (required) {
    rules.push({ required: true, message: '请确认密码', trigger: 'blur' })
  }
  rules.push({
    validator: (_rule: unknown, value: string) => {
      if (!value) return Promise.resolve()
      const pwd = getPassword()
      if (pwd !== undefined && value !== pwd) {
        return Promise.reject(new Error('两次输入的密码不一致'))
      }
      return Promise.resolve()
    },
    trigger: 'blur'
  })
  return rules
}
