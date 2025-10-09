const STORAGE_KEY = 'address_history'
const MAX_HISTORY_SIZE = 10

/**
 * Récupère l'historique des adresses depuis le localStorage
 * @returns {Array<string>} Liste des adresses récentes
 */
export const getAddressHistory = () => {
    try {
        const history = localStorage.getItem(STORAGE_KEY)
        return history ? JSON.parse(history) : []
    } catch (error) {
        console.error('Erreur lors de la récupération de l\'historique des adresses:', error)
        return []
    }
}

/**
 * Sauvegarde une adresse dans l'historique
 * @param {string} address - L'adresse à sauvegarder
 */
export const saveAddressToHistory = (address) => {
    if (!address || typeof address !== 'string' || address.trim() === '') {
        return
    }

    try {
        let history = getAddressHistory()

        // Retirer l'adresse si elle existe déjà pour éviter les doublons
        history = history.filter(item => item !== address)

        // Ajouter la nouvelle adresse au début
        history.unshift(address)

        // Limiter la taille de l'historique
        if (history.length > MAX_HISTORY_SIZE) {
            history = history.slice(0, MAX_HISTORY_SIZE)
        }

        localStorage.setItem(STORAGE_KEY, JSON.stringify(history))
    } catch (error) {
        console.error('Erreur lors de la sauvegarde de l\'adresse:', error)
    }
}

/**
 * Supprime une adresse de l'historique
 * @param {string} address - L'adresse à supprimer
 */
export const removeAddressFromHistory = (address) => {
    try {
        let history = getAddressHistory()
        history = history.filter(item => item !== address)
        localStorage.setItem(STORAGE_KEY, JSON.stringify(history))
    } catch (error) {
        console.error('Erreur lors de la suppression de l\'adresse:', error)
    }
}

/**
 * Vide complètement l'historique
 */
export const clearAddressHistory = () => {
    try {
        localStorage.removeItem(STORAGE_KEY)
    } catch (error) {
        console.error('Erreur lors de la suppression de l\'historique:', error)
    }
}

/**
 * Filtre l'historique en fonction d'une recherche
 * @param {string} searchTerm - Le terme de recherche
 * @returns {Array<string>} Adresses filtrées
 */
export const filterAddressHistory = (searchTerm) => {
    const history = getAddressHistory()
    if (!searchTerm || searchTerm.trim() === '') {
        return history
    }

    const term = searchTerm.toLowerCase()
    return history.filter(address =>
        address.toLowerCase().includes(term)
    )
}