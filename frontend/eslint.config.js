import {
  defineConfigWithVueTs,
  vueTsConfigs
} from '@vue/eslint-config-typescript';
import prettier from 'eslint-config-prettier';
import pluginVue from 'eslint-plugin-vue';
import { globalIgnores } from 'eslint/config';

export default defineConfigWithVueTs(
  globalIgnores(['dist/**', 'node_modules/**', 'src/components/ui/**']),
  pluginVue.configs['flat/recommended'],
  vueTsConfigs.recommended,
  prettier
);
