export const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(date);
};

export const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
};

export const formatRate = (rate) => {
    return rate ? rate.toFixed(2) + ' €' : 'N/A';
};

export const getActiveRate = (cost, date) => {
    console.log(cost);
    // Convertir la date en format comparable
    const formattedDate = new Date(date).toISOString().split('T')[0];

    // Trouver le taux actif en fonction de la date
    const activeRate = cost.form_cost.reimbursement_rates.find(
        (rate) =>
            rate.start_date <= formattedDate &&
            (!rate.end_date || rate.end_date >= formattedDate)
    );

    return activeRate ? activeRate.value : 'N/A';
};
