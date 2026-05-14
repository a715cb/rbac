import { createApp } from 'vue'
import Antd from 'ant-design-vue'
import App from './App.vue'
import router from './router'
import pinia from './stores'
import zhCN from 'ant-design-vue/es/locale/zh_CN'
import dayjs from 'dayjs'
import 'dayjs/locale/zh-cn'
import { authDirective, authDisabledDirective } from './directives/auth'
import { SIcon, SBreadcrumb, SButton } from './components'
import { migrateUnprefixedKeys } from '@/config/migration'

import 'ant-design-vue/dist/reset.css'
import 'virtual:svg-icons-register'
import './styles/global/index.less'
import 'highlight.js/styles/github.css'
import 'highlight.js/lib/common'
import hljsVuePlugin from '@highlightjs/vue-plugin'

dayjs.locale('zh-cn')

migrateUnprefixedKeys()

const app = createApp(App)

app.use(Antd as unknown as typeof Antd, { locale: zhCN })
app.use(router)
app.use(pinia)
app.use(hljsVuePlugin)

app.component('SIcon', SIcon)
app.component('SBreadcrumb', SBreadcrumb)
app.component('SButton', SButton)

app.directive('auth', authDirective)
app.directive('auth-disabled', authDisabledDirective)

app.mount('#app')
