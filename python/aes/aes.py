from crypt import Crypt

### class Aes
#    words_count - число 4-х байтных слов, необходимых при расширении ключа
#        (без учета исходного ключа, т.е., например, для aes-128 всего получится 44 слова, но words_count будет равен 40);
#    key_columns - число столбцов в исходном ключе;
#    rounds - число раундов (всего раундов rounds + 1, например, для  aes-128 rounds = 10, всего раундов 11, см. алгоритм Encrypt в crypt.py).
###

class Aes:
    def __init__(self, words_count, key_columns, rounds):
        self.__crypt = Crypt(words_count, key_columns, rounds)
        self.__key_size = key_columns * 4

    ### Encrypt:
    #    В конец исходного текста будет добавляться число add_zeros, указывающее на то,
    #        сколько байт с конца расшифрованного текста нужно удалить (кол-во добавленных к исх. тексту нулей).
    #    Если последний блок исходных данных не равен 16 байт, то к нему сначала добавляются нулевые байты, затем число add_zeros.
    #    Если посл. блок равен 16 байтам, тогда добавятся 15 нулей и add_zeros = 15.
    #    Если посл. блок равен 15 байтам, то добавится лишь add_zeros = 0
    ###

    def Encrypt(self, text, key):
        bytes_text = bytearray(str(text).encode('utf-8'))
        bytes_key = bytearray(str(key).encode('utf-8'))
        crypt_text = bytearray()

        if len(bytes_key) < self.__key_size:
            print('Ошибка: заданный ключ меньше {0} байт!'.format(self.__key_size))
        else:
            round_keys = self.__crypt.KeyExpansion(bytes_key)
            add_zeros = 16 - (len(bytes_text) % 16 + 1)

            for i in range(add_zeros):
                bytes_text.append(0)

            bytes_text.append(add_zeros)
            
            #деление исх. текста на блоки по 16 байт и их шифровка
            for state in iter(lambda: bytes_text[:16], b''):
                bytes_text = bytes_text[16:]
                crypt_text += self.__crypt.Encrypt(state, round_keys)

        return crypt_text


    def Decrypt(self, crypt_text, key):
        bytes_key = bytearray(str(key).encode('utf-8'))
        decrypt_text = bytearray()

        if len(bytes_key) < self.__key_size:
            print('Ошибка: заданный ключ меньше {0} байт!'.format(self.__key_size))
        else:
            round_keys = self.__crypt.KeyExpansion(bytes_key)
            
            #деление текста на блоки по 16 байт и их расшифровка
            for state in iter(lambda: crypt_text[:16], b''):
                crypt_text = crypt_text[16:]
                decrypt_text += self.__crypt.Decrypt(state, round_keys)

            #удаление лишних байт
            remove_elems = decrypt_text[-1] + 1
            decrypt_text = decrypt_text[:-remove_elems]

            #если ключ был задан неверно, то вернется массив "расшифрованных" байт
            try:
                decrypt_text = decrypt_text.decode('utf-8')
            except UnicodeDecodeError:
                print('Неверно задан ключ! Данный текст зашифрован другим ключем!')

        return decrypt_text


class Aes_128(Aes):
    def __init__(self):
        Aes.__init__(self, 40, 4, 10)


class Aes_192(Aes):
    def __init__(self):
        Aes.__init__(self, 46, 6, 12)


class Aes_256(Aes):
    def __init__(self):
        Aes.__init__(self, 52, 8, 14)