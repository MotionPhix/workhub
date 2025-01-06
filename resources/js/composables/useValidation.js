import { ref } from 'vue'

export function useValidation() {
  const errors = ref({})

  const validate = (rules, data) => {
    errors.value = {}

    Object.keys(rules).forEach(field => {
      const fieldRules = rules[field]

      fieldRules.forEach(rule => {
        const ruleResult = rule(data[field])
        if (ruleResult !== true) {
          errors.value[field] = ruleResult
        }
      })
    })

    return Object.keys(errors.value).length === 0
  }

  return { errors, validate }
}
