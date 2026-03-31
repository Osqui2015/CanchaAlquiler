export interface Client {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    status?: string;
}

export interface ClientsCtx {
    openNewClientModal: () => void;
    editClient: (c: Client) => void;
    disableClient: (c: Client) => void;
    enableClient: (c: Client) => void;
}

export interface Court {
    id: number;
    name: string;
    base_price?: number;
    price_60_min?: number;
}

export interface Reservation {
    id: number;
    start_at?: string;
    is_recurring?: boolean;
    court?: { name?: string };
}

export interface Complex {
    id: number;
    courts?: Court[];
    daily_reservations?: Reservation[];
    availability?: any;
    monthly_reserved_dates?: string[];
}

export interface ReservationsCtx {
    selectedCourtId: string | number;
    dateFilter: { date?: string };
    monthName?: string;
    calendarDays?: Array<string | null>;
    submitDateFilter: () => void;
    openCalendarModal: (complexId: number, date?: string) => void;
    openReservationDetails: (r: Reservation) => void;
    formatMoney: (n: number) => string;
}

export interface ModalidadCatalogs {
    sports?: Array<{ id: number; name: string; slug?: string }>;
}

export interface ModalidadCtx {
    setSport: (sport: any) => void;
    setModalidad: (m: any) => void;
}

export interface RankingItem {
    user_id: number;
    name: string;
    rankid?: number;
    points?: number;
}

export interface RankingCtx {
    loadRankings: () => Promise<void> | void;
}
