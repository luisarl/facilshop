import { defineStore } from 'pinia';

export const usePosStore = defineStore('pos', {
    state: () => ({
        items: [],
        clienteSeleccionado: {
            id_cliente: 1,
            nombre: 'Cliente Mostrador',
            identificacion: 'V-00000000',
            limite_credito: 0,
            saldo_pendiente: 0,
        },
        tipoComprobante: 'TICKET',
        ticketsPausados: JSON.parse(localStorage.getItem('facilshop_tickets_pausados') || '[]'),
        tasaVes: 85.00,
    }),

    getters: {
        subtotal: (state) => {
            return Number(
                state.items.reduce((total, i) => total + (Number(i.cantidad || 0) * Number(i.precio_unitario || 0)), 0).toFixed(2)
            );
        },

        descuentoTotal: (state) => {
            return Number(
                state.items.reduce((total, i) => total + Number(i.descuento || 0), 0).toFixed(2)
            );
        },

        totalUsd: (state) => {
            const total = state.subtotal - state.descuentoTotal;
            return total > 0 ? Number(total.toFixed(2)) : 0.00;
        },

        totalVes: (state) => {
            return Number((state.totalUsd * (Number(state.tasaVes) || 1.0)).toFixed(2));
        },

        totalItems: (state) => {
            return state.items.reduce((total, i) => total + Number(i.cantidad || 0), 0);
        },

        conteoTicketsPausados: (state) => {
            return state.ticketsPausados.length;
        },
    },

    actions: {
        setTasaVes(nuevaTasa) {
            this.tasaVes = Number(nuevaTasa) || 1.0;
        },

        setCliente(cliente) {
            this.clienteSeleccionado = cliente;
        },

        setTipoComprobante(tipo) {
            this.tipoComprobante = tipo;
        },

        agregarProducto(producto, cantidad = 1, unidadId = null) {
            const idUnidadUsar = unidadId || producto.id_unidad;
            const index = this.items.findIndex(
                i => i.id_producto === producto.id_producto && i.id_unidad === idUnidadUsar
            );

            if (index !== -1) {
                this.modificarCantidad(index, this.items[index].cantidad + cantidad);
                return;
            }

            const factor = idUnidadUsar === producto.id_unidad
                ? (Number(producto.equivalencia_unidad) || 1.0)
                : (Number(producto.equivalencia_unidad_secundaria) || 1.0);

            const unidadNombre = idUnidadUsar === producto.id_unidad
                ? (producto.unidad?.nombre || 'Unidad')
                : (producto.unidad_secundaria?.nombre || 'Caja');

            const unidadAbrev = idUnidadUsar === producto.id_unidad
                ? (producto.unidad?.abreviatura || 'UND')
                : (producto.unidad_secundaria?.abreviatura || 'SEC');

            const precio = Number(producto.precio_venta) || 0;
            const cantidadBase = Math.round(cantidad * factor);

            const nuevoItem = {
                id_producto: producto.id_producto,
                sku: producto.sku,
                codigo_barras: producto.codigo_barras,
                nombre: producto.nombre,
                imagen_principal: producto.imagen_principal,
                stock_actual: Number(producto.stock_actual) || 0,
                id_unidad: idUnidadUsar,
                unidad_nombre: unidadNombre,
                unidad_abreviatura: unidadAbrev,
                id_unidad_principal: producto.id_unidad,
                id_unidad_secundaria: producto.id_unidad_secundaria,
                unidad_principal: producto.unidad,
                unidad_secundaria: producto.unidad_secundaria,
                equivalencia_unidad: Number(producto.equivalencia_unidad) || 1.0,
                equivalencia_unidad_secundaria: Number(producto.equivalencia_unidad_secundaria) || 1.0,
                precio_unitario: precio,
                cantidad: cantidad,
                cantidad_base: cantidadBase,
                descuento: 0.00,
                subtotal: Number((cantidad * precio).toFixed(2)),
            };

            this.items.push(nuevoItem);
        },

        modificarCantidad(index, nuevaCantidad) {
            if (index < 0 || index >= this.items.length) return;
            const item = this.items[index];

            const cant = Number(nuevaCantidad);
            if (cant <= 0) {
                this.eliminarItem(index);
                return;
            }

            item.cantidad = cant;
            let factor = 1.0;
            if (item.id_unidad === item.id_unidad_principal) {
                factor = item.equivalencia_unidad;
            } else if (item.id_unidad === item.id_unidad_secundaria) {
                factor = item.equivalencia_unidad_secundaria;
            }

            item.cantidad_base = Math.round(cant * factor);
            item.subtotal = Number(((cant * item.precio_unitario) - item.descuento).toFixed(2));
        },

        cambiarUnidad(index, unidadId) {
            if (index < 0 || index >= this.items.length) return;
            const item = this.items[index];
            item.id_unidad = unidadId;

            let factor = 1.0;
            if (unidadId === item.id_unidad_principal) {
                factor = item.equivalencia_unidad;
                item.unidad_nombre = item.unidad_principal?.nombre || 'Unidad';
                item.unidad_abreviatura = item.unidad_principal?.abreviatura || 'UND';
            } else if (unidadId === item.id_unidad_secundaria) {
                factor = item.equivalencia_unidad_secundaria;
                item.unidad_nombre = item.unidad_secundaria?.nombre || 'Caja';
                item.unidad_abreviatura = item.unidad_secundaria?.abreviatura || 'SEC';
            }

            item.cantidad_base = Math.round(item.cantidad * factor);
        },

        modificarDescuento(index, nuevoDescuento) {
            if (index < 0 || index >= this.items.length) return;
            const item = this.items[index];
            const desc = Math.max(0, Number(nuevoDescuento || 0));
            item.descuento = desc;
            item.subtotal = Number(((item.cantidad * item.precio_unitario) - desc).toFixed(2));
        },

        eliminarItem(index) {
            this.items.splice(index, 1);
        },

        limpiarCarrito() {
            this.items = [];
            this.clienteSeleccionado = {
                id_cliente: 1,
                nombre: 'Cliente Mostrador',
                identificacion: 'V-00000000',
                limite_credito: 0,
                saldo_pendiente: 0,
            };
            this.tipoComprobante = 'TICKET';
        },

        // --- TICKET PARKING (COLA DE VENTAS) ---
        pausarTicket(nota = '') {
            if (this.items.length === 0) return false;

            const ticket = {
                id: 'TKT-PAUSED-' + Date.now(),
                fecha: new Date().toISOString(),
                cliente: { ...this.clienteSeleccionado },
                tipoComprobante: this.tipoComprobante,
                items: JSON.parse(JSON.stringify(this.items)),
                totalUsd: this.totalUsd,
                totalVes: this.totalVes,
                totalItems: this.totalItems,
                nota: nota || `Cliente: ${this.clienteSeleccionado.nombre}`,
            };

            this.ticketsPausados.unshift(ticket);
            this.guardarTicketsEnStorage();
            this.limpiarCarrito();
            return true;
        },

        recuperarTicket(ticketId) {
            const index = this.ticketsPausados.findIndex(t => t.id === ticketId);
            if (index === -1) return false;

            const ticket = this.ticketsPausados[index];
            this.items = JSON.parse(JSON.stringify(ticket.items));
            this.clienteSeleccionado = { ...ticket.cliente };
            this.tipoComprobante = ticket.tipoComprobante || 'TICKET';

            this.ticketsPausados.splice(index, 1);
            this.guardarTicketsEnStorage();
            return true;
        },

        eliminarTicketPausado(ticketId) {
            const index = this.ticketsPausados.findIndex(t => t.id === ticketId);
            if (index !== -1) {
                this.ticketsPausados.splice(index, 1);
                this.guardarTicketsEnStorage();
            }
        },

        guardarTicketsEnStorage() {
            try {
                localStorage.setItem('facilshop_tickets_pausados', JSON.stringify(this.ticketsPausados));
            } catch (e) {
                console.error('Error al persistir tickets en localStorage', e);
            }
        },
    },
});
