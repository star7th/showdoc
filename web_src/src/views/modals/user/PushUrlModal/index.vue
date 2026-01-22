<template>
  <div class="push-url-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('user.wechat_push_url')"
      @close="handleClose"
    >
      <div class="modal-content">
        <!-- 说明文字 -->
        <div class="description">
          <p>{{ $t('user.push_url_desc_title') }}</p>
          <ol class="steps">
            <li>{{ $t('user.push_url_step1') }}</li>
            <li>{{ $t('user.push_url_step2') }}</li>
            <li>{{ $t('user.push_url_step3') }}</li>
            <li>{{ $t('user.push_url_step4') }}</li>
          </ol>
          <p>{{ $t('user.push_url_desc_footer') }}</p>
        </div>

        <div class="form-item">
          <label class="form-label">{{ $t('user.wechat_push_url') }}</label>
          <CommonInput
            v-model="formData.push_url"
            :placeholder="$t('user.push_url_placeholder')"
          />
        </div>
      </div>
      <div class="modal-footer">
        <CommonButton @click="handleClose">
          {{ $t('common.cancel') }}
        </CommonButton>
        <CommonButton type="primary" @click="handleSubmit" :loading="submitting">
          {{ $t('common.save') }}
        </CommonButton>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import request from '@/utils/request'
import Message from '@/components/Message'

const { t } = useI18n()

const props = defineProps<{
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const showId = ref(0)
const submitting = ref(false)
const formData = ref({
  push_url: ''
})

// 获取推送地址
const getPushUrl = async () => {
  try {
    const data = await request('/api/user/getPushUrl', {})
    if (data && data.data) {
      // data.data 可能是对象，需要获取其中的 push_url 字段
      formData.value.push_url = data.data.push_url || data.data
    }
  } catch (error) {
    console.error('获取推送地址失败:', error)
  }
}

const handleSubmit = async () => {
  if (!formData.value.push_url) {
    Message.warning(t('user.push_url_required'))
    return
  }

  submitting.value = true
  try {
    await request('/api/user/savePushUrl', {
      push_url: formData.value.push_url
    })
    Message.success(t('user.save_success'))
    props.onClose(true)
  } catch (error) {
    console.error('保存推送地址失败:', error)
  } finally {
    submitting.value = false
  }
}

const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  showId.value++
  setTimeout(() => {
    show.value = true
    getPushUrl()
  })
})
</script>

<style lang="scss" scoped>
.modal-content {
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);
}

.description {
  margin-bottom: 24px;
  padding: 16px;
  background-color: var(--color-bg-secondary);
  border-radius: 8px;
  font-size: 14px;
  color: var(--color-text-secondary);
  line-height: 1.6;

  p {
    margin: 8px 0;
  }

  .steps {
    margin: 12px 0;
    padding-left: 24px;

    li {
      margin: 6px 0;
    }
  }

  a {
    color: var(--color-primary);
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }
}

.form-item {
  margin-bottom: 20px;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  color: var(--color-text-primary);
  font-weight: 500;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .primary-button {
    width: 160px;
    margin: 0 7.5px;
  }
}
</style>
