/**
 * Utilidades para formateo de numeros segun los requerimientos del sistema:
 * - Separador de miles: punto (.)
 * - Separador de decimales: coma (,)
 */

export function ParsearNumero(valor)
{
    if (valor === null || valor === undefined || valor === '')
    {
        return 0;
    }

    if (typeof valor === 'number')
    {
        return isNaN(valor) ? 0 : valor;
    }

    if (typeof valor === 'string')
    {
        const limpio = valor.trim();
        if (limpio === '')
        {
            return 0;
        }

        if (limpio.includes('.') && limpio.includes(','))
        {
            const UltimoPunto = limpio.lastIndexOf('.');
            const UltimaComa = limpio.lastIndexOf(',');

            if (UltimaComa > UltimoPunto)
            {
                const normalizado = limpio.replace(/\./g, '').replace(',', '.');
                return parseFloat(normalizado) || 0;
            }
            else
            {
                const normalizado = limpio.replace(/,/g, '');
                return parseFloat(normalizado) || 0;
            }
        }

        if (limpio.includes(','))
        {
            const partes = limpio.split(',');
            if (partes.length === 2)
            {
                return parseFloat(limpio.replace(',', '.')) || 0;
            }

            return parseFloat(limpio.replace(/,/g, '')) || 0;
        }

        if (limpio.includes('.'))
        {
            const partes = limpio.split('.');
            if (partes.length === 2)
            {
                return parseFloat(limpio) || 0;
            }

            return parseFloat(limpio.replace(/\./g, '')) || 0;
        }

        return parseFloat(limpio) || 0;
    }

    return 0;
}

export function FormatearNumero(valor, decimales = 2)
{
    if (valor === null || valor === undefined || valor === '')
    {
        return decimales > 0 ? `0,${'0'.repeat(decimales)}` : '0';
    }

    const numero = typeof valor === 'number' ? valor : ParsearNumero(valor);
    if (isNaN(numero))
    {
        return decimales > 0 ? `0,${'0'.repeat(decimales)}` : '0';
    }

    const partes = Math.abs(numero).toFixed(decimales).split('.');
    const entero = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const signo = numero < 0 ? '-' : '';

    if (decimales > 0 && partes.length > 1)
    {
        return `${signo}${entero},${partes[1]}`;
    }

    return `${signo}${entero}`;
}

export function FormatearMoneda(valor, simbolo = '$', decimales = 2)
{
    const formateado = FormatearNumero(valor, decimales);
    if (!simbolo)
    {
        return formateado;
    }

    return `${simbolo} ${formateado}`;
}

export function FormatearCantidad(valor, decimalesMax = 2)
{
    if (valor === null || valor === undefined || valor === '')
    {
        return '0';
    }

    const numero = typeof valor === 'number' ? valor : ParsearNumero(valor);
    if (isNaN(numero))
    {
        return '0';
    }

    if (Number.isInteger(numero))
    {
        return FormatearNumero(numero, 0);
    }

    const redondeado = Number(numero.toFixed(decimalesMax));
    if (Number.isInteger(redondeado))
    {
        return FormatearNumero(redondeado, 0);
    }

    const partes = Math.abs(redondeado).toString().split('.');
    const entero = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const signo = redondeado < 0 ? '-' : '';

    return `${signo}${entero},${partes[1]}`;
}

export default {
    ParsearNumero,
    FormatearNumero,
    FormatearMoneda,
    FormatearCantidad
};
