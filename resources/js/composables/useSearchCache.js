import { ref } from 'vue';

/**
 * Composable para gerenciar cache de buscas no localStorage
 * Cache expira em 15 minutos
 */
export function useSearchCache() {
    const CACHE_PREFIX = 'searchbus_cache_';
    const CACHE_DURATION = 15 * 60 * 1000; // 15 minutos em milissegundos

    /**
     * Gera chave única para a busca
     */
    const generateCacheKey = (from, to, date) => {
        return `${CACHE_PREFIX}${from}_${to}_${date}`;
    };

    /**
     * Verifica se o cache ainda é válido
     */
    const isCacheValid = (timestamp) => {
        const now = Date.now();
        return (now - timestamp) < CACHE_DURATION;
    };

    /**
     * Salva resultado da busca no cache
     */
    const saveToCache = (from, to, date, trips, searchParams) => {
        try {
            const cacheKey = generateCacheKey(from, to, date);
            const cacheData = {
                trips,
                searchParams,
                timestamp: Date.now(),
                expiresAt: Date.now() + CACHE_DURATION
            };

            localStorage.setItem(cacheKey, JSON.stringify(cacheData));
            console.log(`✅ Cache salvo: ${cacheKey}`);
            return true;
        } catch (error) {
            console.error('❌ Erro ao salvar cache:', error);
            return false;
        }
    };

    /**
     * Busca dados do cache
     */
    const getFromCache = (from, to, date) => {
        try {
            const cacheKey = generateCacheKey(from, to, date);
            const cached = localStorage.getItem(cacheKey);

            if (!cached) {
                console.log('⚠️ Cache não encontrado');
                return null;
            }

            const cacheData = JSON.parse(cached);

            // Verifica se o cache ainda é válido
            if (!isCacheValid(cacheData.timestamp)) {
                console.log('⏰ Cache expirado, removendo...');
                localStorage.removeItem(cacheKey);
                return null;
            }

            console.log(`✅ Cache válido encontrado: ${cacheKey}`);
            return {
                trips: cacheData.trips,
                searchParams: cacheData.searchParams,
                fromCache: true,
                cachedAt: new Date(cacheData.timestamp).toLocaleTimeString('pt-BR')
            };
        } catch (error) {
            console.error('❌ Erro ao ler cache:', error);
            return null;
        }
    };

    /**
     * Limpa cache específico
     */
    const clearCache = (from, to, date) => {
        try {
            const cacheKey = generateCacheKey(from, to, date);
            localStorage.removeItem(cacheKey);
            console.log(`🗑️ Cache removido: ${cacheKey}`);
            return true;
        } catch (error) {
            console.error('❌ Erro ao limpar cache:', error);
            return false;
        }
    };

    /**
     * Limpa todo o cache de buscas
     */
    const clearAllCache = () => {
        try {
            const keys = Object.keys(localStorage);
            let count = 0;

            keys.forEach(key => {
                if (key.startsWith(CACHE_PREFIX)) {
                    localStorage.removeItem(key);
                    count++;
                }
            });

            console.log(`🗑️ ${count} cache(s) removido(s)`);
            return count;
        } catch (error) {
            console.error('❌ Erro ao limpar todos os caches:', error);
            return 0;
        }
    };

    /**
     * Limpa caches expirados
     */
    const cleanExpiredCache = () => {
        try {
            const keys = Object.keys(localStorage);
            let count = 0;

            keys.forEach(key => {
                if (key.startsWith(CACHE_PREFIX)) {
                    try {
                        const data = JSON.parse(localStorage.getItem(key));
                        if (!isCacheValid(data.timestamp)) {
                            localStorage.removeItem(key);
                            count++;
                        }
                    } catch (e) {
                        // Se houver erro ao parsear, remove o cache corrompido
                        localStorage.removeItem(key);
                        count++;
                    }
                }
            });

            if (count > 0) {
                console.log(`🧹 ${count} cache(s) expirado(s) removido(s)`);
            }
            return count;
        } catch (error) {
            console.error('❌ Erro ao limpar caches expirados:', error);
            return 0;
        }
    };

    /**
     * Retorna estatísticas do cache
     */
    const getCacheStats = () => {
        try {
            const keys = Object.keys(localStorage);
            const cacheKeys = keys.filter(key => key.startsWith(CACHE_PREFIX));

            let validCount = 0;
            let expiredCount = 0;
            let totalSize = 0;

            cacheKeys.forEach(key => {
                try {
                    const data = localStorage.getItem(key);
                    totalSize += data.length;

                    const parsed = JSON.parse(data);
                    if (isCacheValid(parsed.timestamp)) {
                        validCount++;
                    } else {
                        expiredCount++;
                    }
                } catch (e) {
                    expiredCount++;
                }
            });

            return {
                total: cacheKeys.length,
                valid: validCount,
                expired: expiredCount,
                sizeKB: (totalSize / 1024).toFixed(2)
            };
        } catch (error) {
            console.error('❌ Erro ao obter estatísticas:', error);
            return { total: 0, valid: 0, expired: 0, sizeKB: 0 };
        }
    };

    return {
        saveToCache,
        getFromCache,
        clearCache,
        clearAllCache,
        cleanExpiredCache,
        getCacheStats
    };
}
