/**
 * Utilidades para formateo de fechas y horas segun los requerimientos del sistema:
 * - Fechas: DD-MM-YYYY
 * - Horas: 12 horas con indicador AM/PM
 */

export function ParsearFecha(valor)
{
    if (!valor)
    {
        return null;
    }

    if (valor instanceof Date)
    {
        return isNaN(valor.getTime()) ? null : valor;
    }

    if (typeof valor === 'string')
    {
        const limpio = valor.trim();

        // Caso formato YYYY-MM-DD puro sin componente de hora
        if (/^\d{4}-\d{2}-\d{2}$/.test(limpio))
        {
            const [ano, mes, dia] = limpio.split('-').map(Number);
            return new Date(ano, mes - 1, dia);
        }

        // Caso formato YYYY-MM-DD HH:mm:ss (MySQL datetime estandar)
        const coincidenciaSql = limpio.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
        if (coincidenciaSql)
        {
            const [, ano, mes, dia, horas, minutos, segundos] = coincidenciaSql;
            return new Date(
                Number(ano),
                Number(mes) - 1,
                Number(dia),
                Number(horas),
                Number(minutos),
                segundos ? Number(segundos) : 0
            );
        }

        const parseado = new Date(limpio);
        return isNaN(parseado.getTime()) ? null : parseado;
    }

    if (typeof valor === 'number')
    {
        const parseado = new Date(valor);
        return isNaN(parseado.getTime()) ? null : parseado;
    }

    return null;
}

export function FormatearFecha(valor)
{
    const fecha = ParsearFecha(valor);
    if (!fecha)
    {
        return '-';
    }

    const dia = String(fecha.getDate()).padStart(2, '0');
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const ano = fecha.getFullYear();

    return `${dia}-${mes}-${ano}`;
}

export function FormatearHora(valor, incluirSegundos = false)
{
    const fecha = ParsearFecha(valor);
    if (!fecha)
    {
        return '-';
    }

    let horas = fecha.getHours();
    const minutos = String(fecha.getMinutes()).padStart(2, '0');
    const periodo = horas >= 12 ? 'PM' : 'AM';

    horas = horas % 12;
    horas = horas ? horas : 12; // La hora '0' corresponde a las '12'
    const horasFormateadas = String(horas).padStart(2, '0');

    if (incluirSegundos)
    {
        const segundos = String(fecha.getSeconds()).padStart(2, '0');
        return `${horasFormateadas}:${minutos}:${segundos} ${periodo}`;
    }

    return `${horasFormateadas}:${minutos} ${periodo}`;
}

export function FormatearFechaHora(valor, incluirSegundos = false)
{
    const fecha = ParsearFecha(valor);
    if (!fecha)
    {
        return '-';
    }

    return `${FormatearFecha(fecha)} ${FormatearHora(fecha, incluirSegundos)}`;
}

export default {
    ParsearFecha,
    FormatearFecha,
    FormatearHora,
    FormatearFechaHora
};
